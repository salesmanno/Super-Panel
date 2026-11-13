<?php
declare(strict_types=1);
require_once __DIR__.'/VippsWebhookRegistry.php';

/** No DDL, payment initiation, capture, deletion or automatic registration retry. */
final class EconomyVippsWebhookSetup {
    public function __construct(private PDO $db, private VippsWebhookClient $client = new VippsWebhookClient()) {}

    public function load(string $company, string $id): array {
        $q = $this->db->prepare("SELECT c.*, t.subdomain FROM economy_configuration_data c
            JOIN tenant_companies t ON t.id=c.company_id AND t.is_active=1
            WHERE c.id=? AND c.company_id=? AND LOWER(c.gateway_type)='vipps'");
        $q->execute([$id, $company]);
        $config = $q->fetch(PDO::FETCH_ASSOC);
        if (!$config) throw new RuntimeException('Vipps-oppsett ikke funnet for denne bedriften.');
        // A configuration may be inactive precisely because the user is activating it.
        $q = $this->db->prepare("SELECT id FROM economy_payment_gateways
            WHERE configuration_id=? AND company_id=? AND LOWER(gateway_name)='vipps'");
        $q->execute([$id, $company]);
        if (!$q->fetchColumn()) throw new RuntimeException('Vipps-oppsettet mangler kobling i economy_payment_gateways.');
        return $config;
    }
    private function save(array $config, array $record): array {
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare('SELECT c.*,t.subdomain FROM economy_configuration_data c
                JOIN tenant_companies t ON t.id=c.company_id WHERE c.id=? AND c.company_id=? FOR UPDATE');
            $q->execute([$config['id'], $config['company_id']]);
            $current = $q->fetch(PDO::FETCH_ASSOC);
            if (!$current) throw new RuntimeException('Betalingsoppsettet finnes ikke lenger.');
            if (!hash_equals(VippsWebhookRegistry::binding($config, $config['company_id'], $this->client),
                VippsWebhookRegistry::binding($current, $config['company_id'], $this->client))) {
                throw new RuntimeException('Betalingsoppsettet ble endret. Oppdater status før du fortsetter.');
            }
            $doc = VippsWebhookRegistry::document($current);
            $doc[VippsWebhookRegistry::KEY][VippsWebhookRegistry::environment($config)] = $record;
            $raw = json_encode($doc, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $this->db->prepare('UPDATE economy_configuration_data SET config_data=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND company_id=?')
                ->execute([$raw, $config['id'], $config['company_id']]);
            $this->db->commit();
            $config['config_data'] = $raw;
            return $config;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
    private function remote(array $config): array {
        $response = $this->client->webhookRequest($config, $config['company_id'], 'GET');
        if ($response['code'] !== 200 || !is_array($response['data']['webhooks'] ?? null)) {
            throw new RuntimeException('Kunne ikke hente webhook-status fra Vipps. Ingen registrering utført.');
        }
        return array_values(array_filter($response['data']['webhooks'],
            static fn($w) => is_array($w) && ($w['url'] ?? '') === VippsWebhookRegistry::callback($config)));
    }
    private function summary(array $config, array $matches): array {
        $record = VippsWebhookRegistry::record($config);
        $ready = $this->client->receiverReady($config, $config['company_id'], VippsWebhookRegistry::callback($config), $record);
        $registered = count($matches) === 1 && ($matches[0]['id'] ?? null) === ($record['id'] ?? null)
            && is_string($record['secret'] ?? null) && $record['secret'] !== ''
            && ($record['binding'] ?? '') === VippsWebhookRegistry::binding($config, $config['company_id'], $this->client)
            && is_array($matches[0]['events'] ?? null)
            && array_diff(VippsWebhookRegistry::EVENTS, $matches[0]['events']) === [];
        $state = $registered && $ready ? 'registered' : ((!$ready || $matches || $record) ? 'attention' : 'not_registered');
        $message = !$ready ? 'Betalingsmottakeren er ikke klargjort for felles databaselagring. Se integrasjonsveiledningen.'
            : ($state === 'registered' ? 'Webhook registrert. Faktisk levering er ikke verifisert.'
            : ($state === 'not_registered' ? 'Ingen webhook registrert. Trykk På for å registrere og aktivere.'
            : 'Registrering må avklares: kontroller abonnement, signeringshemmelighet, miljø og hendelser. Ingen ny registrering utføres automatisk.'));
        return ['state'=>$state, 'message'=>$message, 'environment'=>VippsWebhookRegistry::environment($config),
            'callback_url'=>VippsWebhookRegistry::callback($config), 'receiver_ready'=>$ready,
            'delivery_verified'=>false, 'is_active'=>(bool)$config['is_active']];
    }
    public function status(string $company, string $id): array {
        $config = $this->load($company, $id);
        return $this->summary($config, $this->remote($config));
    }
    public function deactivate(string $company, string $id): void {
        $lockName = 'vipps-setup:'.substr(hash('sha256', $company), 0, 40);
        $q = $this->db->prepare('SELECT GET_LOCK(?,0)');
        $q->execute([$lockName]);
        if ((int)$q->fetchColumn() !== 1) throw new RuntimeException('Vipps-aktivering pågår allerede. Oppdater status om litt.');
        try {
            $this->load($company, $id);
            $this->db->beginTransaction();
            $q = $this->db->prepare('SELECT id FROM tenant_companies WHERE id=? FOR UPDATE');
            $q->execute([$company]);
            $this->db->prepare('UPDATE economy_configuration_data SET is_active=0,updated_at=CURRENT_TIMESTAMP WHERE id=? AND company_id=?')
                ->execute([$id, $company]);
            $this->db->prepare("UPDATE economy_payment_gateways SET is_active=0,updated_at=CURRENT_TIMESTAMP WHERE configuration_id=? AND company_id=? AND LOWER(gateway_name)='vipps'")
                ->execute([$id, $company]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        } finally {
            $q = $this->db->prepare('SELECT RELEASE_LOCK(?)');
            $q->execute([$lockName]);
        }
    }
    public function activate(string $company, string $id): array {
        $lockName = 'vipps-setup:'.substr(hash('sha256', $company), 0, 40);
        $q = $this->db->prepare('SELECT GET_LOCK(?,0)');
        $q->execute([$lockName]);
        if ((int)$q->fetchColumn() !== 1) throw new RuntimeException('Vipps-aktivering pågår allerede. Oppdater status om litt.');
        try {
            $config = $this->load($company, $id);
            $matches = $this->remote($config);
            $summary = $this->summary($config, $matches);
            if ($summary['state'] !== 'registered') {
                if (!$summary['receiver_ready'] || $matches || VippsWebhookRegistry::record($config)) return $summary;
                $record = ['binding'=>VippsWebhookRegistry::binding($config, $company, $this->client),
                    'attempted_at'=>gmdate('c')];
                // Persist intent BEFORE the non-idempotent POST. Any uncertain result blocks another POST.
                $config = $this->save($config, $record);
                $response = $this->client->webhookRequest($config, $company, 'POST', [
                    'url'=>VippsWebhookRegistry::callback($config), 'events'=>VippsWebhookRegistry::EVENTS,
                ]);
                if ($response['code'] !== 201 || !is_string($response['data']['id'] ?? null)
                    || $response['data']['id'] === '' || !is_string($response['data']['secret'] ?? null)
                    || $response['data']['secret'] === '') {
                    throw new RuntimeException('Registreringen ble ikke bekreftet. Ikke gjenta blindt; oppdater status og kontroller Vipps.');
                }
                $record['id'] = $response['data']['id'];
                $record['secret'] = $response['data']['secret'];
                $config = $this->save($config, $record);
                $summary = $this->summary($config, $this->remote($config));
            }
            if ($summary['state'] !== 'registered') return $summary;
            $this->db->beginTransaction();
            try {
                // Short lock only for the local switch, never around external requests.
                $q = $this->db->prepare('SELECT id,subdomain FROM tenant_companies WHERE id=? FOR UPDATE');
                $q->execute([$company]);
                $companyRow = $q->fetch(PDO::FETCH_ASSOC);
                $q = $this->db->prepare('SELECT * FROM economy_configuration_data WHERE id=? AND company_id=? FOR UPDATE');
                $q->execute([$id, $company]);
                $current = $q->fetch(PDO::FETCH_ASSOC);
                $current['subdomain'] = $companyRow['subdomain'] ?? '';
                if (VippsWebhookRegistry::binding($current, $company, $this->client) !== VippsWebhookRegistry::binding($config, $company, $this->client)
                    || VippsWebhookRegistry::digest(VippsWebhookRegistry::record($current)) !== VippsWebhookRegistry::digest(VippsWebhookRegistry::record($config))) {
                    throw new RuntimeException('Oppsettet ble endret under aktivering. Oppdater status.');
                }
                $this->db->prepare('UPDATE economy_configuration_data SET is_active=1, updated_at=CURRENT_TIMESTAMP WHERE id=? AND company_id=?')
                    ->execute([$id, $company]);
                $this->db->prepare("UPDATE economy_payment_gateways SET is_active=1, updated_at=CURRENT_TIMESTAMP WHERE configuration_id=? AND company_id=? AND LOWER(gateway_name)='vipps'")
                    ->execute([$id, $company]);
                $this->db->commit();
            } catch (Throwable $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                throw $e;
            }
            $summary['is_active'] = true;
            return $summary;
        } finally {
            $q = $this->db->prepare('SELECT RELEASE_LOCK(?)');
            $q->execute([$lockName]);
        }
    }
}
