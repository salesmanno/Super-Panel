<?php

/**
 * GlobalIncomeExpenseService - Global inntekts/utgifts logging
 * 
 * Brukes for å logge globale inntekter og utgifter som ikke er direkte
 * knyttet til unified_ledger, eller som supplerer unified_ledger data.
 */
class GlobalIncomeExpenseService {
    private $db;
    private $tenantId;
    
    public function __construct($db, $tenantId) {
        $this->db = $db;
        $this->tenantId = $tenantId;
    }
    
    /**
     * Log global income or expense
     * 
     * @param array $params {
     *   transaction_type: string ('income' or 'expense')
     *   amount: float
     *   module_source: string ('events', 'sales', 'rental', 'reservations')
     *   source_id: string (ID from module table)
     *   unified_ledger_id: string|null (FK to unified_ledger)
     *   description: string|null
     *   category: string|null
     *   notes: string|null
     *   created_by: string|null
     * }
     * @return array ['success' => bool, 'message' => string, 'id' => string]
     */
    public function log($params) {
        try {
            // Validate required params
            $required = ['transaction_type', 'amount', 'module_source', 'source_id'];
            foreach ($required as $field) {
                if (!isset($params[$field])) {
                    throw new Exception("Mangler påkrevd felt: {$field}");
                }
            }
            
            // Validate transaction_type
            if (!in_array($params['transaction_type'], ['income', 'expense'])) {
                throw new Exception("Ugyldig transaction_type. Må være 'income' eller 'expense'");
            }
            
            // Generate ID
            $id = bin2hex(random_bytes(18));
            
            // Insert into global_income_expenses
            $stmt = $this->db->prepare("
                INSERT INTO global_income_expenses (
                    id, tenant_id, transaction_type, amount,
                    description, category, unified_ledger_id,
                    source_id, module_source, notes, created_by,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $id,
                $this->tenantId,
                $params['transaction_type'],
                $params['amount'],
                $params['description'] ?? null,
                $params['category'] ?? null,
                $params['unified_ledger_id'] ?? null,
                $params['source_id'],
                $params['module_source'],
                $params['notes'] ?? null,
                $params['created_by'] ?? null
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Global inntekt/utgift logget',
                    'id' => $id
                ];
            } else {
                throw new Exception('Kunne ikke lagre global inntekt/utgift');
            }
            
        } catch (Exception $e) {
            error_log("GlobalIncomeExpenseService error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete global income/expense
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM global_income_expenses
                WHERE id = ? AND tenant_id = ?
            ");
            
            $result = $stmt->execute([$id, $this->tenantId]);
            
            return [
                'success' => $result && $stmt->rowCount() > 0,
                'message' => $result ? 'Global inntekt/utgift slettet' : 'Ikke funnet'
            ];
            
        } catch (Exception $e) {
            error_log("GlobalIncomeExpenseService delete error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get records by source
     */
    public function getBySource($moduleSource, $sourceId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM global_income_expenses
                WHERE tenant_id = ? AND module_source = ? AND source_id = ?
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$this->tenantId, $moduleSource, $sourceId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("GlobalIncomeExpenseService getBySource error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate summary for a source
     */
    public function getSummary($moduleSource, $sourceId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    transaction_type,
                    SUM(amount) as total
                FROM global_income_expenses
                WHERE tenant_id = ? AND module_source = ? AND source_id = ?
                GROUP BY transaction_type
            ");
            
            $stmt->execute([$this->tenantId, $moduleSource, $sourceId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $income = 0;
            $expense = 0;
            
            foreach ($results as $row) {
                if ($row['transaction_type'] === 'income') {
                    $income = (float)$row['total'];
                } else {
                    $expense = (float)$row['total'];
                }
            }
            
            return [
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense
            ];
            
        } catch (Exception $e) {
            error_log("GlobalIncomeExpenseService getSummary error: " . $e->getMessage());
            return ['income' => 0, 'expense' => 0, 'net' => 0];
        }
    }
}
