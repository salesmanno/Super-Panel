<?php

class ProductCatalogService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get or create a module in system_modules
     * @param string $moduleKey
     * @return string module_id
     */
    private function getOrCreateModule($moduleKey) {
        $stmt = $this->db->prepare("SELECT id FROM system_modules WHERE module_key = ?");
        $stmt->execute([$moduleKey]);
        $moduleId = $stmt->fetchColumn();
        
        if ($moduleId) {
            return $moduleId;
        }
        
        // Module doesn't exist - create it
        $dbType = getDBType();
        
        // Module metadata
        $moduleMetadata = [
            'events' => ['name' => 'Arrangementer', 'description' => 'Arrangementsmodul', 'icon' => 'calendar-event'],
            'sales' => ['name' => 'Salg', 'description' => 'Salgsmodul', 'icon' => 'cart'],
            'rental' => ['name' => 'Utleie', 'description' => 'Utleiemodul', 'icon' => 'key'],
            'membership' => ['name' => 'Medlemskap', 'description' => 'Medlemskapsmodul', 'icon' => 'people'],
            'reservations' => ['name' => 'Reservasjoner', 'description' => 'Reservasjonssystem', 'icon' => 'calendar-check']
        ];
        
        $meta = $moduleMetadata[$moduleKey] ?? ['name' => ucfirst($moduleKey), 'description' => ucfirst($moduleKey), 'icon' => 'circle'];
        
        if ($dbType === 'mysql') {
            $insertStmt = $this->db->prepare("
                INSERT INTO system_modules (id, module_key, name, description, icon, is_active, created_at) 
                VALUES (UUID(), ?, ?, ?, ?, 1, NOW())
            ");
        } else {
            $insertStmt = $this->db->prepare("
                INSERT INTO system_modules (module_key, name, description, icon, is_active, created_at) 
                VALUES (?, ?, ?, ?, true, NOW())
                RETURNING id
            ");
        }
        
        $insertStmt->execute([$moduleKey, $meta['name'], $meta['description'], $meta['icon']]);
        
        // Fetch the newly created module ID
        $stmt->execute([$moduleKey]);
        $moduleId = $stmt->fetchColumn();
        
        if (!$moduleId) {
            throw new Exception("Failed to create module '$moduleKey'");
        }
        
        return $moduleId;
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
            // Get or create module
            $moduleId = $this->getOrCreateModule($moduleKey);
            
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
        
        // Fetch details from source tables and filter out deleted products
        foreach ($outputProducts as $op) {
            $productDetails = $this->getProductDetails($op['product_type'], $op['product_id']);
            
            // Skip products that no longer exist in source table
            if ($productDetails === null) {
                // Log and optionally clean up orphaned output_products entries
                error_log("Orphaned output_product found: {$op['id']} (type: {$op['product_type']}, source_id: {$op['product_id']})");
                continue;
            }
            
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
            $dbType = getDBType();
            
            switch ($productType) {
                case 'event':
                    $stmt = $this->db->prepare("SELECT * FROM events_eventdata WHERE id = ? AND is_active = " . dbBool(true));
                    break;
                    
                case 'product':
                case 'sales_item': // Legacy support
                    if ($dbType === 'mysql') {
                        $stmt = $this->db->prepare("SELECT * FROM sales_products WHERE id = ? AND is_active = " . dbBool(true));
                    } else {
                        $stmt = $this->db->prepare("SELECT * FROM sales_items WHERE id = ? AND is_active = " . dbBool(true));
                    }
                    break;
                    
                case 'rental':
                    if ($dbType === 'mysql') {
                        $stmt = $this->db->prepare("SELECT * FROM rental_objects WHERE id = ? AND is_available = " . dbBool(true));
                    } else {
                        $stmt = $this->db->prepare("SELECT * FROM rental_objects WHERE object_id = ? AND is_available = " . dbBool(true));
                    }
                    break;
                    
                case 'membership':
                    if ($dbType === 'mysql') {
                        $stmt = $this->db->prepare("SELECT * FROM membership_plans WHERE id = ? AND is_active = " . dbBool(true));
                    } else {
                        $stmt = $this->db->prepare("SELECT * FROM x_membership_plan WHERE plan_id = ? AND is_active = " . dbBool(true));
                    }
                    break;
                    
                case 'service':
                    $stmt = $this->db->prepare("SELECT * FROM reservation_services WHERE id = ?");
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
    
    /**
     * Sync product data from source tables to output_products
     * Updates name and price fields to match current source data
     * 
     * @param string|null $companyId - If provided, only sync for this company
     * @param string|null $productType - If provided, only sync this product type
     * @return array - Statistics about synced records
     */
    public function syncProductData($companyId = null, $productType = null) {
        $stats = ['updated' => 0, 'errors' => 0];
        
        try {
            // Build query to get output_products
            $query = "SELECT id, product_type, product_id FROM output_products WHERE 1=1";
            $params = [];
            
            if ($companyId) {
                $query .= " AND company_id = ?";
                $params[] = $companyId;
            }
            
            if ($productType) {
                $query .= " AND product_type = ?";
                $params[] = $productType;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $outputProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Sync each product
            foreach ($outputProducts as $op) {
                try {
                    $sourceData = $this->getSourceProductData($op['product_type'], $op['product_id']);
                    
                    if ($sourceData) {
                        $updateStmt = $this->db->prepare("
                            UPDATE output_products 
                            SET name = ?, 
                                price = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id = ?
                        ");
                        
                        $updateStmt->execute([
                            $sourceData['name'],
                            $sourceData['price'],
                            $op['id']
                        ]);
                        
                        $stats['updated']++;
                    }
                } catch (Exception $e) {
                    error_log("Error syncing product {$op['id']}: " . $e->getMessage());
                    $stats['errors']++;
                }
            }
            
        } catch (Exception $e) {
            error_log("ProductCatalogService syncProductData error: " . $e->getMessage());
            throw $e;
        }
        
        return $stats;
    }
    
    /**
     * Get name and price from source product table
     * 
     * @param string $productType
     * @param string $productId
     * @return array|null - ['name' => string, 'price' => float] or null
     */
    private function getSourceProductData($productType, $productId) {
        try {
            switch ($productType) {
                case 'event':
                    $stmt = $this->db->prepare("
                        SELECT title as name, ticket_price as price 
                        FROM events_eventdata 
                        WHERE id = ?
                    ");
                    break;
                    
                case 'product':
                case 'sales_item':
                    // Support both MySQL (sales_products) and PostgreSQL (sales_items)
                    $dbType = getDBType();
                    if ($dbType === 'mysql') {
                        $stmt = $this->db->prepare("
                            SELECT name, selling_price as price 
                            FROM sales_products 
                            WHERE id = ?
                        ");
                    } else {
                        $stmt = $this->db->prepare("
                            SELECT item_name as name, price 
                            FROM sales_items 
                            WHERE id = ?
                        ");
                    }
                    break;
                    
                case 'membership':
                    // Support both MySQL (membership_plans) and PostgreSQL (x_membership_plan)
                    $dbType = getDBType();
                    if ($dbType === 'mysql') {
                        $stmt = $this->db->prepare("
                            SELECT name, price 
                            FROM membership_plans 
                            WHERE id = ?
                        ");
                    } else {
                        $stmt = $this->db->prepare("
                            SELECT plan_name as name, price 
                            FROM x_membership_plan 
                            WHERE plan_id = ?
                        ");
                    }
                    break;
                    
                case 'rental':
                    // Support both MySQL (rental_objects with id) and PostgreSQL (rental_objects with object_id)
                    $dbType = getDBType();
                    if ($dbType === 'mysql') {
                        $stmt = $this->db->prepare("
                            SELECT name, daily_rate as price 
                            FROM rental_objects 
                            WHERE id = ?
                        ");
                    } else {
                        $stmt = $this->db->prepare("
                            SELECT object_name as name, daily_price as price 
                            FROM rental_objects 
                            WHERE object_id = ?
                        ");
                    }
                    break;
                    
                default:
                    return null;
            }
            
            $stmt->execute([$productId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
            
        } catch (Exception $e) {
            error_log("Error fetching source product data: " . $e->getMessage());
            return null;
        }
    }
}
