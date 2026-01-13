<?php
class ShareLinkService {
    private $db;
    private $companyId;
    private $subdomain;
    
    const TYPE_EVENT = 'A';
    const TYPE_SERVICE = 'T';
    const TYPE_MEMBERSHIP = 'M';
    
    private static $typeTableMap = [
        'A' => 'events_eventdata',
        'T' => 'reservation_services',
        'M' => 'membership_plans'
    ];
    
    public function __construct($db, $companyId, $subdomain = null) {
        $this->db = $db;
        $this->companyId = $companyId;
        $this->subdomain = $subdomain;
        
        if (!$subdomain) {
            $stmt = $db->prepare("SELECT subdomain FROM tenant_companies WHERE id = ?");
            $stmt->execute([$companyId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->subdomain = $result ? $result['subdomain'] : '';
        }
    }
    
    public function generateCode($length = 5) {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
    
    public function ensureShareCode($type, $itemId) {
        if (!isset(self::$typeTableMap[$type])) {
            throw new Exception("Ugyldig type: $type");
        }
        
        $table = self::$typeTableMap[$type];
        
        $stmt = $this->db->prepare("SELECT share_code FROM $table WHERE id = ? AND company_id = ?");
        $stmt->execute([$itemId, $this->companyId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception("Element ikke funnet");
        }
        
        if (!empty($result['share_code'])) {
            return $result['share_code'];
        }
        
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = $this->generateCode();
            
            $checkStmt = $this->db->prepare("SELECT id FROM $table WHERE share_code = ?");
            $checkStmt->execute([$code]);
            
            if (!$checkStmt->fetch()) {
                $updateStmt = $this->db->prepare("UPDATE $table SET share_code = ? WHERE id = ? AND company_id = ?");
                $updateStmt->execute([$code, $itemId, $this->companyId]);
                return $code;
            }
        }
        
        throw new Exception("Kunne ikke generere unik kode");
    }
    
    public function getShareLink($type, $itemId) {
        $shareCode = $this->ensureShareCode($type, $itemId);
        $baseUrl = "https://{$this->subdomain}.salesman.no";
        return $baseUrl . '/' . $type . $shareCode;
    }
    
    public function getEventShareLink($eventId) {
        return $this->getShareLink(self::TYPE_EVENT, $eventId);
    }
    
    public function getServiceShareLink($serviceId) {
        return $this->getShareLink(self::TYPE_SERVICE, $serviceId);
    }
    
    public function getMembershipShareLink($membershipPlanId) {
        return $this->getShareLink(self::TYPE_MEMBERSHIP, $membershipPlanId);
    }
    
    public function resolveShareCode($fullCode) {
        if (strlen($fullCode) < 2) {
            return null;
        }
        
        $type = strtoupper($fullCode[0]);
        $code = substr($fullCode, 1);
        
        if (!isset(self::$typeTableMap[$type])) {
            return null;
        }
        
        $table = self::$typeTableMap[$type];
        
        $stmt = $this->db->prepare("SELECT id FROM $table WHERE share_code = ? AND company_id = ?");
        $stmt->execute([$code, $this->companyId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        return [
            'type' => $type,
            'table' => $table,
            'item_id' => $result['id']
        ];
    }
}
