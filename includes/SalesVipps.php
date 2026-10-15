<?php
declare(strict_types=1);

require_once __DIR__.'/SalesVippsFailure.php';

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
        $valid=false;$data=[];
        try {
            if(strlen($raw)<=65536) {
                $decoded=json_decode($raw,true,64,JSON_THROW_ON_ERROR);
                $valid=is_array($decoded) && str_starts_with(ltrim($raw),'{');
                if($valid)$data=$decoded;
            }
        } catch(JsonException $e) {}
        return ['code'=>$code,'data'=>$data,'json_valid'=>$valid];
    }
    private function transport(string $url,string $method,array $headers,?array $body,string $operation,string $environment): array {
        try {return $this->send($url,$method,$headers,$body);}
        catch(SalesVippsFailure $e){throw $e;}
        catch(JsonException $e){throw new SalesVippsFailure('invalid_request',$operation,0,$environment);}
        catch(Throwable $e){throw new SalesVippsFailure('network_failure',$operation,0,$environment);}
    }
    public function request(array $configuration,string $company,string $method,string $path,?array $body=null,?string $idempotency=null): array {
        $keys=$this->credentials($configuration,$company);
        $environment=(int)$configuration['test_mode']===1?'test':'production';
        $base=$environment==='test'?'https://apitest.vipps.no':'https://api.vipps.no';
        $headers=['Content-Type: application/json','client_id: '.$keys['client_id'],'client_secret: '.$keys['client_secret'],
            'Ocp-Apim-Subscription-Key: '.$keys['subscription_key'],'Merchant-Serial-Number: '.$keys['merchant_serial_number'],
            'Vipps-System-Name: Salesman','Vipps-System-Version: 1.0'];
        $auth=$this->transport($base.'/accesstoken/get','POST',$headers,[],'access_token',$environment);
        if ($auth['code']!==200 || ($auth['json_valid']??true)!==true || !is_string($auth['data']['access_token']??null)
            || $auth['data']['access_token']==='' || preg_match('/[\x00-\x20\x7f]/',$auth['data']['access_token']))
            throw $this->failure($auth,'access_token',$environment);
        $headers=['Content-Type: application/json','Authorization: Bearer '.$auth['data']['access_token'],
            'Ocp-Apim-Subscription-Key: '.$keys['subscription_key'],'Merchant-Serial-Number: '.$keys['merchant_serial_number'],
            'Vipps-System-Name: Salesman','Vipps-System-Version: 1.0'];
        if ($idempotency!==null) $headers[]='Idempotency-Key: '.$idempotency;
        $operation=$method==='GET'?'payment_lookup':($path===''?'payment_creation':
            (str_ends_with($path,'/capture')?'payment_capture':'payment_cancel'));
        $response=$this->transport($base.'/epayment/v1/payments'.$path,$method,$headers,$body,$operation,$environment);
        $response['sales_environment']=$environment;
        return $response;
    }
    public function verifyWebhook(array $config,string $company,string $body,array $server): bool {
        try {
            $this->credentials($config,$company);
            $secret=is_string($config['webhook_secret']??null)?trim($config['webhook_secret']):'';
            // A webhook's signing secret is separate from payment credentials.
            // Use the selected company's configuration, never an unrelated
            // server-wide fallback. The signing secret is never required
            // to list the gateway, initiate payment or poll payment status.
        }catch(Throwable $e){return false;}
        $date=$server['HTTP_X_MS_DATE']??'';$host=$server['HTTP_HOST']??'';$path=$server['REQUEST_URI']??'';
        $hash=base64_encode(hash('sha256',$body,true));
        if ($secret==='' || abs(time()-(strtotime($date)?:0))>300
            || !hash_equals($hash,$server['HTTP_X_MS_CONTENT_SHA256']??'')) return false;
        $signature=base64_encode(hash_hmac('sha256',"POST\n$path\n$date;$host;$hash",$secret,true));
        return hash_equals('HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature='.$signature,$server['HTTP_AUTHORIZATION']??'');
    }
    public function failure(array $response,string $operation='payment_lookup',string $environment='unknown'): SalesVippsFailure {
        return SalesVippsFailure::fromResponse($response,$operation,$response['sales_environment']??$environment);
    }
    public function paymentError(array $response): string {
        return $this->failure($response,'payment_creation')->getMessage();
    }
}
