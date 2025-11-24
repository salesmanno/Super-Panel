<?php
/**
 * Export PostgreSQL data to MySQL-compatible SQL
 * Run this to generate SQL file for upload to production MySQL server
 */

require_once 'config/database.php';

$db = getDBConnection();
if (!$db) {
    die("ERROR: Could not connect to database\n");
}

$output = "-- MySQL Export from PostgreSQL\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// Export tenant_companies
$output .= "-- Tenant Companies\n";
$stmt = $db->query("SELECT * FROM tenant_companies ORDER BY id");
$companies = $stmt->fetchAll();
foreach ($companies as $row) {
    $output .= sprintf(
        "INSERT INTO tenant_companies (id, name, subdomain, is_active, created_at) VALUES (%d, %s, %s, %d, %s);\n",
        $row['id'],
        $db->quote($row['name']),
        $db->quote($row['subdomain']),
        $row['is_active'] ? 1 : 0,
        $db->quote($row['created_at'])
    );
}

// Export tenant_staff
$output .= "\n-- Tenant Staff\n";
$stmt = $db->query("SELECT * FROM tenant_staff ORDER BY id");
$staff = $stmt->fetchAll();
foreach ($staff as $row) {
    $output .= sprintf(
        "INSERT INTO tenant_staff (id, first_name, last_name, email, phone, created_at) VALUES (%d, %s, %s, %s, %s, %s);\n",
        $row['id'],
        $db->quote($row['first_name']),
        $db->quote($row['last_name']),
        $db->quote($row['email']),
        $db->quote($row['phone'] ?? ''),
        $db->quote($row['created_at'])
    );
}

// Export tenant_admins
$output .= "\n-- Tenant Admins\n";
$stmt = $db->query("SELECT * FROM tenant_admins ORDER BY id");
$admins = $stmt->fetchAll();
foreach ($admins as $row) {
    $output .= sprintf(
        "INSERT INTO tenant_admins (id, username, password_hash, company_id, tenant_staff_id, is_active, last_login, created_at) VALUES (%d, %s, %s, %d, %s, %d, %s, %s);\n",
        $row['id'],
        $db->quote($row['username']),
        $db->quote($row['password_hash']),
        $row['company_id'],
        $row['tenant_staff_id'] ?? 'NULL',
        $row['is_active'] ? 1 : 0,
        $row['last_login'] ? $db->quote($row['last_login']) : 'NULL',
        $db->quote($row['created_at'])
    );
}

// Export modules
$output .= "\n-- Modules\n";
$stmt = $db->query("SELECT * FROM modules ORDER BY module_id");
$modules = $stmt->fetchAll();
foreach ($modules as $row) {
    $output .= sprintf(
        "INSERT INTO modules (module_id, name, description, is_active, created_at) VALUES (%d, %s, %s, %d, %s);\n",
        $row['module_id'],
        $db->quote($row['name']),
        $db->quote($row['description'] ?? ''),
        $row['is_active'] ? 1 : 0,
        $db->quote($row['created_at'])
    );
}

// Export tenant_admin_modules
$output .= "\n-- Tenant Admin Modules\n";
$stmt = $db->query("SELECT * FROM tenant_admin_modules ORDER BY id");
$adminModules = $stmt->fetchAll();
foreach ($adminModules as $row) {
    $output .= sprintf(
        "INSERT INTO tenant_admin_modules (id, tenant_admin_id, module_id, granted_by) VALUES (%d, %d, %d, %s);\n",
        $row['id'],
        $row['tenant_admin_id'],
        $row['module_id'],
        $row['granted_by'] ?? 'NULL'
    );
}

// Export tenant_company_modules
$output .= "\n-- Tenant Company Modules\n";
$stmt = $db->query("SELECT * FROM tenant_company_modules ORDER BY id");
$companyModules = $stmt->fetchAll();
foreach ($companyModules as $row) {
    $output .= sprintf(
        "INSERT INTO tenant_company_modules (id, company_id, module_id, is_active) VALUES (%d, %d, %d, %d);\n",
        $row['id'],
        $row['company_id'],
        $row['module_id'],
        $row['is_active'] ? 1 : 0
    );
}

$output .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

// Save to file
$filename = 'mysql_export_' . date('Y-m-d_His') . '.sql';
file_put_contents($filename, $output);

echo "✅ Export complete!\n";
echo "📁 File: $filename\n";
echo "📊 Exported:\n";
echo "   - " . count($companies) . " companies\n";
echo "   - " . count($staff) . " staff members\n";
echo "   - " . count($admins) . " admins\n";
echo "   - " . count($modules) . " modules\n";
echo "   - " . count($adminModules) . " admin-module relations\n";
echo "   - " . count($companyModules) . " company-module relations\n";
echo "\n💡 Upload this file to your MySQL server and run it.\n";
