<?php
declare(strict_types=1);
require_once __DIR__.'/SalesMoney.php';
require_once __DIR__.'/SalesVipps.php';
require_once __DIR__.'/SalesWebhookAuth.php';
require_once __DIR__.'/SalesProducts.php';
require_once __DIR__.'/SalesCart.php';
require_once __DIR__.'/SalesOrders.php';
require_once __DIR__.'/SalesReceipts.php';
require_once __DIR__.'/SalesRegisterActions.php';
require_once __DIR__.'/EmailService.php';
require_once __DIR__.'/entity_form_input.php';
require_once __DIR__.'/active_payment_methods.php';
require_once __DIR__.'/UnifiedLedgerService.php';
require_once __DIR__.'/ProductCatalogService.php';

class SalesConflict extends RuntimeException {}

final class SalesService {
    use SalesProducts, SalesCart, SalesOrders, SalesReceipts, SalesRegisterActions;
    private array $actor;
    private bool $taxRegistered;
    public function __construct(private PDO $db,private string $company,private string $admin,
        private string $sessionToken,private SalesVipps $provider=new SalesVipps(),private ?SalesWebhookAuth $systemAuth=null) {
        if($systemAuth!==null) {
            if($systemAuth->company!==$company)throw new TenantAuditException('Ugyldig systemtilgang');
            $this->actor=tenantAuditSystemActor($db,$company,$systemAuth->job);
        } else {
            $this->actor=entityFormRequireModuleAccess($db,$company,'sales_product');
            if ($this->actor['id']!==$admin) throw new TenantAuditException('Ugyldig bruker');
        }
        $tax=$this->q('SELECT tax_registered FROM tenant_companies WHERE id=?',[$company])->fetchColumn();
        if ($tax===false) throw new OutOfBoundsException('Firma ikke funnet');
        $this->taxRegistered=(int)$tax===1;
    }
    private function q(string $sql,array $args=[]): PDOStatement {
        $q=$this->db->prepare($sql);$q->execute($args);return $q;
    }
    private function transaction(callable $operation): mixed {
        if ($this->db->inTransaction()) throw new LogicException('Sales krever en kort, egen transaksjon');
        $this->db->beginTransaction();
        try {
            $this->actor=$this->systemAuth?tenantAuditSystemActor($this->db,$this->company,$this->systemAuth->job):entityFormRequireModuleAccess($this->db,$this->company,'sales_product');
            // Also serialises creation of the per-user/company cart and stock/product
            // operations. No external HTTP call is made while this lock is held.
            if (!$this->q('SELECT id FROM tenant_companies WHERE id=? FOR UPDATE',[$this->company])->fetchColumn()) throw new OutOfBoundsException('Firma ikke funnet');
            $result=$this->systemAuth && method_exists($this->db,'withExplicitAudit')?$this->db->withExplicitAudit($operation):$operation();
            $this->db->commit();return $result;
        } catch(Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    public static function cartOwnerToken(string $company,string $admin): string {
        if($company==='' || $admin==='')throw new InvalidArgumentException('Kassen mangler bruker eller bedrift');
        // This is an internal lookup key, never an authentication credential.
        return 'sales-admin-v2:'.hash('sha256',self::json([$company,$admin]));
    }
    private static function uuid(): string {
        $h=bin2hex(random_bytes(16));return substr($h,0,8).'-'.substr($h,8,4).'-4'.substr($h,13,3).'-a'.substr($h,17,3).'-'.substr($h,20,12);
    }
    private static function json(array $data): string {return json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);}
    private static function text(mixed $value,int $max): string {
        if (!is_string($value) || str_contains($value,"\0") || mb_strlen($value)>$max) throw new InvalidArgumentException('Ugyldig tekstverdi');
        return trim($value);
    }
    private function checkSchema(): void {
        foreach (['sales_cart_items'=>['form_data'],'sales_products'=>['stock_quantity','is_backorder'],
            'sales_orders'=>['payment_gateway_id'],'sales_orders_items'=>['form_data']] as $table=>$columns) {
            foreach ($columns as $column) {
                if (!$this->q('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',[$table,$column])->fetchColumn())
                    throw new RuntimeException("Sales-strukturen mangler $table.$column. Ingen tabeller opprettes automatisk.");
            }
        }
        if ($this->q("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sales_cart_items' AND INDEX_NAME='uq_cart_product'")->fetchColumn())
            throw new RuntimeException('Fjern uq_cart_product før den nye kassen tas i bruk.');
    }
    public function bootstrap(): array {
        $this->checkSchema();
        return $this->productList()+$this->cartResult()+['csrf_token'=>entityFormCsrfToken(),'register_state'=>$this->registerState()];
    }
    public function nextCustomer(): array {
        return $this->transaction(function(){
            if ($this->pendingOrderId()) throw new SalesConflict('Avklar den ventende betalingen før neste kunde');
            $cart=$this->activeCart();
            if ($cart && $this->q('SELECT COUNT(*) FROM sales_cart_items WHERE cart_id=?',[$cart['id']])->fetchColumn())
                throw new SalesConflict('Handlekurven inneholder varer. Fullfør eller fjern varene først.');
            $cart??=$this->createCart();
            return ['cart'=>$this->cartView($cart)];
        });
    }
}
