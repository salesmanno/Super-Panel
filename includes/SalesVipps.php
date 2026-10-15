<?php
declare(strict_types=1);

/**
 * Payment credentials come from the selected economy configuration.
 * Despite its historical name, client_secret_encrypted stores the plain
 * client secret in this project. Never return these values to the browser.
 */
class SalesVipps {
    protected function credentials(array $configuration,string $company): array {
        if (($configuration['company_id']??null)!==$company || empty($configuration['configuration_id'])
            || !in_array($configuration['test_mode']??null,[0,1,'0','1'],true))
            throw new RuntimeException('Vipps-konfigureringen tilhører ikke firmaet eller har ugyldig driftsmodus');
        $keys=[];
        foreach (['client_id'=>'client_id','client_secret'=>'client_secret_encrypted',
            'subscription_key'=>'subscription_key','merchant_serial_number'=>'merchant_serial_number'] as $field=>$column) {
            $value=$configuration[$column]??null;
            if (!is_string($value) || trim($value)==='' || preg_match('/[\r\n\x00]/',$value))
                throw new RuntimeException('Vipps-nøkkelsettet i Økonomi er ufullstendig eller ugyldig');
            $keys[$field]=$value;
        }
        return $keys;
    }
    public function configured(array $configuration,string $company): bool {
        try {$this->credentials($configuration,$company);return true;}
        catch (RuntimeException $e) {return false;}
    }
    public function binding(array $configuration,string $company): string {
        $keys=$this->credentials($configuration,$company);
        return hash('sha256',json_encode([$configuration['configuration_id'],$company,(int)$configuration['test_mode'],
            $keys['client_id'],$keys['merchant_serial_number']],JSON_THROW_ON_ERROR));
    }
    protected function send(string $url,string $method,array $headers,?array $body): array {
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HTTPHEADER=>$headers,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>18,
            CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
        if ($body!==null) curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_THROW_ON_ERROR));
        $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        // Never log raw requests, responses, phone numbers or tokens.
        if ($raw===false || $code===0) throw new RuntimeException('Vipps-svaret er uavklart. Kontroller status før du prøver igjen.');
        try {$data=json_decode($raw,true,64,JSON_THROW_ON_ERROR);}
        catch(JsonException $e) {$data=[];}
        return ['code'=>$code,'data'=>is_array($data)?$data:[]];
    }
    public function request(array $configuration,string $company,string $method,string $path,?array $body=null,?string $idempotency=null): array {
        $keys=$this->credentials($configuration,$company);
        $base=(int)$configuration['test_mode']===1?'https://apitest.vipps.no':'https://api.vipps.no';
        $headers=['Content-Type: application/json','client_id: '.$keys['client_id'],'client_secret: '.$keys['client_secret'],
            'Ocp-Apim-Subscription-Key: '.$keys['subscription_key'],'Merchant-Serial-Number: '.$keys['merchant_serial_number'],
            'Vipps-System-Name: Salesman','Vipps-System-Version: 1.0'];
        $auth=$this->send($base.'/accesstoken/get','POST',$headers,[]);
        if ($auth['code']!==200 || !is_string($auth['data']['access_token']??null))
            throw new RuntimeException('Vipps kunne ikke autentisere serverens betalingsoppsett');
        $headers=['Content-Type: application/json','Authorization: Bearer '.$auth['data']['access_token'],
            'Ocp-Apim-Subscription-Key: '.$keys['subscription_key'],'Merchant-Serial-Number: '.$keys['merchant_serial_number'],
            'Vipps-System-Name: Salesman','Vipps-System-Version: 1.0'];
        if ($idempotency!==null) $headers[]='Idempotency-Key: '.$idempotency;
        return $this->send($base.'/epayment/v1/payments'.$path,$method,$headers,$body);
    }
    public function verifyWebhook(array $config,string $company,string $body,array $server): bool {
        try {
            $secret=$this->credentials($config,$company)['webhook_secret']??'';
            // A webhook's signing secret is separate from payment credentials.
            // Preserve existing optional webhook setup; it is never required
            // to list the gateway, initiate payment or poll payment status.
            if($secret==='') {
                $map=json_decode(getenv('VIPPS_CREDENTIALS_JSON')?:'{}',true,64,JSON_THROW_ON_ERROR);
                $entry=$map[$config['configuration_id']]??null;
                if(is_array($entry) && ($entry['company_id']??null)===$company
                    && (int)($entry['test_mode']??-1)===(int)$config['test_mode'])
                    $secret=is_string($entry['webhook_secret']??null)?$entry['webhook_secret']:'';
            }
        }catch(Throwable $e){return false;}
        $date=$server['HTTP_X_MS_DATE']??'';$host=$server['HTTP_HOST']??'';$path=$server['REQUEST_URI']??'';
        $hash=base64_encode(hash('sha256',$body,true));
        if ($secret==='' || abs(time()-(strtotime($date)?:0))>300
            || !hash_equals($hash,$server['HTTP_X_MS_CONTENT_SHA256']??'')) return false;
        $signature=base64_encode(hash_hmac('sha256',"POST\n$path\n$date;$host;$hash",$secret,true));
        return hash_equals('HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature='.$signature,$server['HTTP_AUTHORIZATION']??'');
    }
}
