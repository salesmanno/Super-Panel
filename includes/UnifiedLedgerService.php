<?php

/**
 * UnifiedLedgerService - Sentralisert økonomisk logging
 * 
 * Brukes av alle moduler for å logge transaksjoner i unified_ledger
 * - Sales: Produktsalg
 * - Events: Billettinntekter
 * - Membership: Medlemskapsbetalinger
 * - Rental: Utleieinntekter
 * - Reservations: Tjenestesalg
 */
class UnifiedLedgerService {
    private $db;
    private $companyId;
    
    public function __construct($db, $companyId) {
        $this->db = $db;
        $this->companyId = $companyId;
    }
    
    /**
     * Log transaction to unified ledger
     * 
     * @param array $params {
     *   order_id: string (unique order/transaction ID)
     *   module_source: string ('sales', 'events', 'membership', 'rental', 'reservations')
     *   item_type: string ('product', 'event', 'membership', 'rental', 'service')
     *   source_id: string (ID from original module table)
     *   item_name: string (name of product/service)
     *   quantity: int (default: 1)
     *   selling_price: float (total selling price)
     *   cost_price: float|null (optional cost price for profit tracking)
     *   customer_name: string|null
     *   customer_email: string|null
     *   payment_method: string|null
     *   payment_status: string ('completed', 'pending', 'failed')
     *   status: string ('active', 'cancelled', 'refunded')
     *   transaction_date: string|null (ISO datetime, defaults to now)
     *   metadata: array|null (additional module-specific data)
     * }
     * @return array ['success' => bool, 'message' => string, 'ledger_id' => string]
     */
    public function logTransaction($params) {
        try {
            // Validate required params
            $required = ['order_id', 'module_source', 'item_type', 'source_id', 'item_name', 'selling_price'];
            foreach ($required as $field) {
                if (!isset($params[$field])) {
                    throw new Exception("Mangler påkrevd felt: {$field}");
                }
            }
            
            // Generate ledger ID
            $ledgerId = $this->generateUUID();
            
            // Prepare metadata
            $metadata = isset($params['metadata']) && is_array($params['metadata']) 
                ? json_encode($params['metadata']) 
                : null;
            
            // Set defaults
            $quantity = $params['quantity'] ?? 1;
            $costPrice = $params['cost_price'] ?? null;
            $paymentStatus = $params['payment_status'] ?? 'completed';
            $status = $params['status'] ?? 'active';
            $transactionDate = $params['transaction_date'] ?? date('Y-m-d H:i:s');
            
            // Calculate profit
            $profit = $costPrice !== null 
                ? $params['selling_price'] - $costPrice 
                : null;
            
            // Insert into unified_ledger
            $stmt = $this->db->prepare("
                INSERT INTO unified_ledger (
                    id, order_id, company_id,
                    module_source, item_type, source_id,
                    item_name, quantity,
                    selling_price, cost_price, profit,
                    customer_name, customer_email,
                    payment_method, payment_status,
                    status, transaction_date, metadata
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $ledgerId,
                $params['order_id'],
                $this->companyId,
                $params['module_source'],
                $params['item_type'],
                $params['source_id'],
                $params['item_name'],
                $quantity,
                $params['selling_price'],
                $costPrice,
                $profit,
                $params['customer_name'] ?? null,
                $params['customer_email'] ?? null,
                $params['payment_method'] ?? null,
                $paymentStatus,
                $status,
                $transactionDate,
                $metadata
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Transaksjon logget i unified ledger',
                    'ledger_id' => $ledgerId
                ];
            } else {
                throw new Exception('Kunne ikke lagre transaksjon');
            }
            
        } catch (Exception $e) {
            error_log("UnifiedLedgerService error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update transaction status (for cancellations, refunds)
     */
    public function updateTransactionStatus($moduleSource, $sourceId, $newStatus) {
        try {
            $stmt = $this->db->prepare("
                UPDATE unified_ledger 
                SET status = ?, updated_at = NOW()
                WHERE company_id = ? 
                  AND module_source = ? 
                  AND source_id = ?
            ");
            
            $result = $stmt->execute([
                $newStatus,
                $this->companyId,
                $moduleSource,
                $sourceId
            ]);
            
            return [
                'success' => $result,
                'message' => $result ? 'Status oppdatert' : 'Feil ved oppdatering'
            ];
            
        } catch (Exception $e) {
            error_log("UnifiedLedgerService updateStatus error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get transaction by source
     */
    public function getTransactionBySource($moduleSource, $sourceId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM unified_ledger 
                WHERE company_id = ? 
                  AND module_source = ? 
                  AND source_id = ?
                LIMIT 1
            ");
            $stmt->execute([$this->companyId, $moduleSource, $sourceId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("UnifiedLedgerService getTransaction error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate UUID (v4)
     */
    private function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
