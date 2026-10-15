<?php
declare(strict_types=1);

/**
 * Internal capability. No constructor or HTTP flag can grant system access.
 * Webhooks must prove possession of this order's original merchant secret.
 */
final class SalesWebhookAuth {
    private function __construct(public readonly string $company,public readonly string $orderId,public readonly string $job) {}
    public static function verify(PDO $db,SalesVipps $provider,string $body,array $server): self {
        $event=json_decode($body,true,32,JSON_THROW_ON_ERROR);
        $reference=$event['reference']??'';
        if(!is_string($reference) || !preg_match('/^sales-([0-9a-f-]{36})$/D',$reference,$m))throw new RuntimeException('Ugyldig webhook');
        $q=$db->prepare("SELECT o.company_id,l.metadata FROM sales_orders o JOIN unified_ledger l ON l.order_id=o.id AND l.company_id=o.company_id AND l.module_source='sales' WHERE o.id=? ORDER BY l.source_id LIMIT 1");
        $q->execute([$m[1]]);$row=$q->fetch(PDO::FETCH_ASSOC);
        if(!$row)throw new RuntimeException('Ugyldig webhook');
        $control=json_decode($row['metadata'],true,32,JSON_THROW_ON_ERROR)['sales']??[];
        if(($control['version']??null)!==1 || ($control['reference']??null)!==$reference)throw new RuntimeException('Ugyldig webhook');
        $q=$db->prepare('SELECT id AS configuration_id,company_id,gateway_type,test_mode,
            client_id,client_secret_encrypted,subscription_key,merchant_serial_number FROM economy_configuration_data WHERE id=? AND company_id=?');
        $q->execute([$control['configuration_id'],$row['company_id']]);$config=$q->fetch(PDO::FETCH_ASSOC);
        if(!$config || strtolower(trim($config['gateway_type']))!=='vipps' || !$provider->verifyWebhook($config,$row['company_id'],$body,$server)
            || !hash_equals((string)$control['account_binding'],$provider->binding($config,$row['company_id'])))throw new RuntimeException('Ugyldig webhook');
        return new self($row['company_id'],$m[1],'sales_vipps_webhook');
    }
    public static function forCron(PDO $db,string $id): self {
        if(PHP_SAPI!=='cli')throw new RuntimeException('Kun CLI');
        $q=$db->prepare("SELECT company_id FROM sales_orders WHERE id=? AND status='pending'");$q->execute([$id]);$company=$q->fetchColumn();
        if(!$company)throw new RuntimeException('Ingen ventende ordre');
        return new self($company,$id,'sales_payment_reconcile');
    }
}
