<?php
declare(strict_types=1);

require_once __DIR__ . '/SalesVipps.php';

/** Shared wire/storage contract. Copy this file and SalesVipps dependencies to the receiver. */
class VippsWebhookClient extends SalesVipps {
    public function selected(array $config, string $company): array {
        if (!in_array($config['test_mode'] ?? null, [0, 1, '0', '1'], true)) {
            throw new RuntimeException('Velg gyldig Vipps-miljø i betalingsoppsettet.');
        }
        if ((int)$config['test_mode'] === 1) {
            foreach (['client_id'=>'test_client_id', 'client_secret_encrypted'=>'test_client_secret',
                'subscription_key'=>'test_subscription_key', 'merchant_serial_number'=>'test_merchant_serial_number'] as $to=>$from) {
                $config[$to] = $config[$from] ?? null;
            }
        }
        $config['configuration_id'] = $config['id'] ?? $config['configuration_id'] ?? '';
        $this->credentials($config, $company);
        return $config;
    }

    public function webhookRequest(array $config, string $company, string $method, ?array $body = null): array {
        $config = $this->selected($config, $company);
        $keys = $this->credentials($config, $company);
        $base = (int)$config['test_mode'] === 1 ? 'https://apitest.vipps.no' : 'https://api.vipps.no';
        $headers = ['Content-Type: application/json', 'client_id: '.$keys['client_id'],
            'client_secret: '.$keys['client_secret'], 'Ocp-Apim-Subscription-Key: '.$keys['subscription_key'],
            'Merchant-Serial-Number: '.$keys['merchant_serial_number'],
            'Vipps-System-Name: salesman', 'Vipps-System-Version: 1.0'];
        $auth = $this->send($base.'/accesstoken/get', 'POST', $headers, []);
        $token = $auth['data']['access_token'] ?? null;
        if ($auth['code'] !== 200 || !is_string($token) || $token === '' || preg_match('/[\x00-\x20\x7f]/', $token)) {
            throw new RuntimeException('Vipps avviste nøkkelsettet for valgt betalingsmiljø.');
        }
        return $this->send($base.'/webhooks/v1/webhooks', $method, [
            'Content-Type: application/json', 'Authorization: Bearer '.$token,
            'Ocp-Apim-Subscription-Key: '.$keys['subscription_key'],
            'Merchant-Serial-Number: '.$keys['merchant_serial_number'],
            'Vipps-System-Name: salesman', 'Vipps-System-Version: 1.0',
        ], $body);
    }

    public function receiverReady(array $config, string $company, string $url, array $record): bool {
        $selected = $this->selected($config, $company);
        $challenge = bin2hex(random_bytes(24));
        $environment = VippsWebhookRegistry::environment($config);
        $signature = VippsWebhookRegistry::proof('request', $challenge, $config['id'], $environment, $url, '', $selected['client_secret_encrypted']);
        $probeUrl = $url.'?'.http_build_query(['salesman_webhook_probe'=>'1', 'challenge'=>$challenge,
            'configuration_id'=>$config['id'], 'environment'=>$environment]);
        try {
            $response = $this->send($probeUrl, 'GET', ['Accept: application/json',
                'X-Salesman-Setup-Authorization: '.$signature], null);
            $expected = VippsWebhookRegistry::proof('response', $challenge, $config['id'], $environment, $url,
                VippsWebhookRegistry::digest($record), $selected['client_secret_encrypted']);
            return $response['code'] === 200 && ($response['data']['storage_version'] ?? null) === 1
                && is_string($response['data']['proof'] ?? null) && hash_equals($expected, $response['data']['proof']);
        } catch (Throwable $e) {
            return false;
        }
    }
}

final class VippsWebhookRegistry {
    public const KEY = '_salesman_vipps_webhooks_v1';
    public const EVENTS = [
        'epayments.payment.authorized.v1', 'epayments.payment.captured.v1',
        'epayments.payment.aborted.v1', 'epayments.payment.expired.v1',
        'epayments.payment.cancelled.v1', 'epayments.payment.terminated.v1',
    ];
    public static function environment(array $config): string {
        return (int)$config['test_mode'] === 1 ? 'test' : 'production';
    }
    public static function document(array $config): array {
        $raw = $config['config_data'] ?? null;
        if ($raw === null || $raw === '') return [];
        $data = json_decode((string)$raw, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data) || (array_is_list($data) && $data !== [])) {
            throw new RuntimeException('config_data må være et JSON-objekt.');
        }
        return $data;
    }
    public static function record(array $config): array {
        $record = self::document($config)[self::KEY][self::environment($config)] ?? [];
        if (!is_array($record)) throw new RuntimeException('Ugyldig lagret webhook-oppsett.');
        return $record;
    }
    public static function digest(array $record): string {
        ksort($record);
        return hash('sha256', json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
    public static function proof(string $kind, string $challenge, string $id, string $env, string $url, string $digest, string $key): string {
        return hash_hmac('sha256', implode("\n", ['salesman-webhook-v1', $kind, $challenge, $id, $env, $url, $digest]), $key);
    }
    public static function callback(array $config): string {
        $subdomain = strtolower((string)($config['subdomain'] ?? ''));
        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $subdomain)) {
            throw new RuntimeException('Firmaet mangler et gyldig, serverlagret subdomene.');
        }
        return 'https://'.$subdomain.'.salesman.no/api/vipps_callback.php';
    }
    public static function binding(array $config, string $company, VippsWebhookClient $client): string {
        $selected = $client->selected($config, $company);
        return hash('sha256', json_encode([$config['id'], $company, self::environment($config),
            $selected['client_id'], $selected['merchant_serial_number'], self::callback($config),
            hash('sha256', $selected['client_secret_encrypted']), hash('sha256', $selected['subscription_key'])], JSON_THROW_ON_ERROR));
    }

    /** Receiver integration: call only after resolving the company from the trusted hostname. */
    public static function receiverProbe(PDO $db, array $config, array $query, array $server): array {
        if (($server['REQUEST_METHOD'] ?? '') !== 'GET' || ($query['salesman_webhook_probe'] ?? '') !== '1'
            || ($query['configuration_id'] ?? '') !== ($config['id'] ?? '')
            || ($query['environment'] ?? '') !== self::environment($config)
            || !is_string($query['challenge'] ?? null) || !preg_match('/^[a-f0-9]{48}$/D', $query['challenge'])) {
            throw new RuntimeException('Ugyldig oppsettskontroll.');
        }
        $client = new VippsWebhookClient();
        $selected = $client->selected($config, $config['company_id']);
        $url = self::callback($config);
        if (strtolower((string)($server['HTTP_HOST'] ?? '')) !== parse_url($url, PHP_URL_HOST)
            || parse_url((string)($server['REQUEST_URI'] ?? ''), PHP_URL_PATH) !== '/api/vipps_callback.php') {
            throw new RuntimeException('Feil betalingsmottaker.');
        }
        $expected = self::proof('request', $query['challenge'], $config['id'], self::environment($config), $url, '', $selected['client_secret_encrypted']);
        if (!hash_equals($expected, (string)($server['HTTP_X_SALESMAN_SETUP_AUTHORIZATION'] ?? ''))) {
            throw new RuntimeException('Uautorisert oppsettskontroll.');
        }
        // Reload from the shared database; never attest to a cached/local-file secret.
        $q = $db->prepare('SELECT config_data FROM economy_configuration_data WHERE id=? AND company_id=?');
        $q->execute([$config['id'], $config['company_id']]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new RuntimeException('Betalingsoppsett ikke funnet.');
        $config['config_data'] = $row['config_data'];
        return ['storage_version'=>1, 'proof'=>self::proof('response', $query['challenge'], $config['id'],
            self::environment($config), $url, self::digest(self::record($config)), $selected['client_secret_encrypted'])];
    }

    /** Receiver POST: the caller must already bind company/configuration/environment to the order. */
    public static function verifyDelivery(array $config, string $company, string $body, array $server): bool {
        try {
            if (($server['REQUEST_METHOD'] ?? '') !== 'POST') return false;
            $client = new VippsWebhookClient();
            $record = self::record($config);
            if (!is_string($record['secret'] ?? null) || $record['secret'] === ''
                || !hash_equals(self::binding($config, $company, $client), (string)($record['binding'] ?? ''))
                || ($server['HTTP_HOST'] ?? '') !== parse_url(self::callback($config), PHP_URL_HOST)
                || parse_url((string)($server['REQUEST_URI'] ?? ''), PHP_URL_PATH) !== '/api/vipps_callback.php') return false;
            $selected = $client->selected($config, $company);
            $payload = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($payload) || !is_string($payload['msn'] ?? null)
                || !hash_equals($selected['merchant_serial_number'], $payload['msn'])) return false;
            $selected['webhook_secret'] = $record['secret'];
            return $client->verifyWebhook($selected, $company, $body, $server);
        } catch (Throwable $e) {
            return false;
        }
    }
}
