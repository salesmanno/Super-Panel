<?php
require_once 'includes/functions.php';

try {
    $db = getDB();
    
    // Get all super users
    $stmt = $db->query("SELECT super_id, username FROM super_users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== CREATING MISSING PRIVILEGES ===\n\n";
    
    foreach ($users as $user) {
        $super_id = $user['super_id'];
        $username = $user['username'];
        
        // Check if user has privileges
        $stmt = $db->prepare("SELECT COUNT(*) FROM super_user_privileges WHERE super_id = ?");
        $stmt->execute([$super_id]);
        $has_privileges = $stmt->fetchColumn() > 0;
        
        if (!$has_privileges) {
            echo "Creating default privileges for user: $username (ID: $super_id)\n";
            
            // Create default privileges with all modules enabled for testing
            $stmt = $db->prepare("
                INSERT INTO super_user_privileges (
                    super_id, use_sales, can_edit_sales, use_webmail, can_edit_webmail,
                    use_reservations, can_edit_reservations, use_membership, can_edit_membership,
                    use_rental, can_edit_rental, use_events, can_edit_events,
                    use_economy, can_edit_economy, use_admin, updated_at
                ) VALUES (?, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, NOW())
            ");
            
            if ($stmt->execute([$super_id])) {
                echo "✅ Created privileges for $username\n";
            } else {
                echo "❌ Failed to create privileges for $username\n";
            }
        } else {
            echo "User $username (ID: $super_id) already has privileges\n";
        }
    }
    
    echo "\n=== FINAL PRIVILEGE STATUS ===\n";
    $stmt = $db->query("
        SELECT su.username, su.super_id, 
               sup.use_sales, sup.use_webmail, sup.use_rental, sup.use_events, sup.use_admin
        FROM super_users su
        LEFT JOIN super_user_privileges sup ON su.super_id = sup.super_id
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "User: {$row['username']} (ID: {$row['super_id']})\n";
        echo "  - Sales: " . ($row['use_sales'] ? 'Yes' : 'No') . "\n";
        echo "  - Webmail: " . ($row['use_webmail'] ? 'Yes' : 'No') . "\n";  
        echo "  - Rental: " . ($row['use_rental'] ? 'Yes' : 'No') . "\n";
        echo "  - Events: " . ($row['use_events'] ? 'Yes' : 'No') . "\n";
        echo "  - Admin: " . ($row['use_admin'] ? 'Yes' : 'No') . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>