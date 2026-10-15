<?php
declare(strict_types=1);

/**
 * Credentials are server-environment only, keyed by configuration UUID and
 * bound to company + test mode. No legacy database-key fallback.
 */
class SalesVipps {
    protected function credentials(array $configuration,string $company): array {
        try {$map=json_decode(getenv('VIPPS_CREDENTIALS_JSON')?:'{}',true,64,JSON_THROW_ON_ERROR);}
        catch (JsonException $e) {throw new RuntimeException('Vipps sitt sikre serveroppsett er ugyldig');}
        $keys=$map[$configuration['configuration_id']]??null;
        if (!is_array($keys) || ($keys['company_id']??'')!==$company
            || (int)($keys['test_mode']??-1)!==(int)$configuration['test_mode'])
            throw new RuntimeException('Vipps-nøkler mangler i serverens sikre miljø for dette betalingsoppsettet');
        foreach (['client_id','client_secret','subscription_key','merchant_serial_number'] as $field) {
            if (!is_string($keys[$field]??null) || $keys[$field]==='' || preg_match('/[\r\n]/',$keys[$field]))
                throw new RuntimeException('Hele Vipps-nøkkelsettet må konfigureres på serveren');
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
        try {$secret=$this->credentials($config,$company)['webhook_secret']??'';}catch(Throwable $e){return false;}
        $date=$server['HTTP_X_MS_DATE']??'';$host=$server['HTTP_HOST']??'';$path=$server['REQUEST_URI']??'';
        $hash=base64_encode(hash('sha256',$body,true));
        if ($secret==='' || abs(time()-(strtotime($date)?:0))>300
            || !hash_equals($hash,$server['HTTP_X_MS_CONTENT_SHA256']??'')) return false;
        $signature=base64_encode(hash_hmac('sha256',"POST\n$path\n$date;$host;$hash",$secret,true));
        return hash_equals('HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature='.$signature,$server['HTTP_AUTHORIZATION']??'');
    }
}
