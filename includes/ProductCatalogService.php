<?php

class ProductCatalogService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Auto-generate output_products entry for any product
     * 
     * @param string $companyId
     * @param string $moduleKey - 'events', 'sales', 'rental', 'membership'
     * @param string $productId - ID from source table
     * @param array $options - is_visible, is_public, is_marketplace_featured
     * @param string $createdBy - tenant_admin_id
     * @return string output_product_id
     */
    public function createProductEntry($companyId, $moduleKey, $productId, $options = [], $createdBy = null) {
        try {
            // Get module_id from system_modules
            $stmt = $this->db->prepare("SELECT id FROM system_modules WHERE module_key = ?");
            $stmt->execute([$moduleKey]);
            $moduleId = $stmt->fetchColumn();
            
            if (!$moduleId) {
                throw new Exception("Module '$moduleKey' not found");
            }
            
            // Map module_key to product_type (CLEAN: updated mapping)
            $productTypeMap = [
                'events' => 'event',
                'sales' => 'product',
                'product' => 'product',
                'rental' => 'rental',
                'membership' => 'membership',
                'service' => 'service'
            ];
            
            $productType = $productTypeMap[$moduleKey] ?? $moduleKey;
            
            // Generate output_product_id
            $outputProductId = bin2hex(random_bytes(16));
            
            // Insert into output_products
            $stmt = $this->db->prepare("
                INSERT INTO output_products (
                    id,
                    company_id,
                    module_id,
                    product_type,
                    product_id,
                    is_visible,
                    is_public,
                    is_marketplace_featured,
                    marketplace_priority,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $outputProductId,
                $companyId,
                $moduleId,
                $productType,
                $productId,
                $options['is_visible'] ?? true,
                $options['is_public'] ?? true,
                $options['is_marketplace_featured'] ?? false,
                $options['marketplace_priority'] ?? 0,
                $createdBy
            ]);
            
            return $outputProductId;
            
        } catch (Exception $e) {
            error_log("ProductCatalogService error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all products from output_products catalog
     * Joins with source module tables to get full product details
     */
    public function getAllProducts($companyId, $filters = []) {
        $products = [];
        
        // Get all output_products for this company
        $query = "
            SELECT 
                op.*,
                sm.module_key,
                sm.module_name
            FROM output_products op
            JOIN system_modules sm ON op.module_id = sm.id
            WHERE op.company_id = ?
        ";
        
        $params = [$companyId];
        
        if (isset($filters['is_visible'])) {
            $query .= " AND op.is_visible = ?";
            $params[] = $filters['is_visible'];
        }
        
        if (isset($filters['is_public'])) {
            $query .= " AND op.is_public = ?";
            $params[] = $filters['is_public'];
        }
        
        if (isset($filters['is_marketplace_featured'])) {
            $query .= " AND op.is_marketplace_featured = ?";
            $params[] = $filters['is_marketplace_featured'];
        }
        
        $query .= " ORDER BY op.marketplace_priority DESC, op.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        $outputProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch details from source tables
        foreach ($outputProducts as $op) {
            $productDetails = $this->getProductDetails($op['product_type'], $op['product_id']);
            
            $products[] = array_merge($op, [
                'details' => $productDetails
            ]);
        }
        
        return $products;
    }
    
    /**
     * Get product details from source table
     */
    private function getProductDetails($productType, $productId) {
        try {
            switch ($productType) {
                case 'event':
                    $stmt = $this->db->prepare("SELECT * FROM events_eventdata WHERE id = ?");
                    break;
                case 'product':
                case 'sales_item': // Legacy support
                    $stmt = $this->db->prepare("SELECT * FROM sales_products WHERE id = ?");
                    break;
                case 'rental':
                    $stmt = $this->db->prepare("SELECT * FROM rental_objects WHERE id = ?");
                    break;
                case 'membership':
                    $stmt = $this->db->prepare("SELECT * FROM membership_plans WHERE id = ?");
                    break;
                case 'service':
                    $stmt = $this->db->prepare("SELECT * FROM service_bookings WHERE id = ?");
                    break;
                default:
                    return null;
            }
            
            $stmt->execute([$productId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error fetching product details: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update visibility/marketplace settings
     */
    public function updateProductVisibility($outputProductId, $settings) {
        $updates = [];
        $params = [];
        
        if (isset($settings['is_visible'])) {
            $updates[] = "is_visible = ?";
            $params[] = $settings['is_visible'];
        }
        
        if (isset($settings['is_public'])) {
            $updates[] = "is_public = ?";
            $params[] = $settings['is_public'];
        }
        
        if (isset($settings['is_marketplace_featured'])) {
            $updates[] = "is_marketplace_featured = ?";
            $params[] = $settings['is_marketplace_featured'];
        }
        
        if (isset($settings['marketplace_priority'])) {
            $updates[] = "marketplace_priority = ?";
            $params[] = $settings['marketplace_priority'];
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $outputProductId;
        
        $query = "UPDATE output_products SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }
    
    /**
     * Soft delete - hide product without removing it
     */
    public function hideProduct($outputProductId) {
        return $this->updateProductVisibility($outputProductId, [
            'is_visible' => false,
            'is_public' => false,
            'is_marketplace_featured' => false
        ]);
    }
}
