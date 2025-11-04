<?php
// Setup PostgreSQL database with complete schema and seed data
// This script converts MySQL schema to PostgreSQL and imports it

require_once 'config/database.php';

try {
    $conn = getDBConnection();
    $dbType = getDBType();
    
    if ($dbType !== 'postgresql') {
        die("❌ This script only runs on PostgreSQL. Current database: $dbType\n");
    }
    
    echo "🔧 Setting up PostgreSQL database for Salesman SaaS platform...\n\n";
    
    echo "📦 Creating missing database tables...\n";
    
    // Create tables that don't exist (using CREATE TABLE IF NOT EXISTS)
    
    // System modules
    $conn->exec("
        CREATE TABLE IF NOT EXISTS system_modules (
            id VARCHAR(36) PRIMARY KEY DEFAULT gen_random_uuid()::text,
            module_key VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(50) DEFAULT 'business',
            icon VARCHAR(100),
            version VARCHAR(20) DEFAULT '1.0.0',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Tenant companies
    $conn->exec("
        CREATE TABLE IF NOT EXISTS tenant_companies (
            id VARCHAR(36) PRIMARY KEY DEFAULT gen_random_uuid()::text,
            name VARCHAR(255) NOT NULL,
            legal_name VARCHAR(255),
            entity_type VARCHAR(20) DEFAULT 'company',
            subdomain VARCHAR(100) UNIQUE NOT NULL,
            domain VARCHAR(255) UNIQUE,
            industry VARCHAR(100),
            company_size VARCHAR(50),
            contact_email VARCHAR(255),
            contact_phone VARCHAR(50),
            website VARCHAR(255),
            logo_url VARCHAR(500),
            address_street VARCHAR(255),
            address_city VARCHAR(255),
            address_state VARCHAR(255),
            address_zip VARCHAR(50),
            address_country VARCHAR(100) DEFAULT 'Norway',
            tax_id VARCHAR(100),
            tax_registered BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            trial_ends_at TIMESTAMP NULL,
            onboarded_by VARCHAR(36),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Tenant staff
    $conn->exec("
        CREATE TABLE IF NOT EXISTS tenant_staff (
            id VARCHAR(36) PRIMARY KEY DEFAULT gen_random_uuid()::text,
            company_id VARCHAR(36) NOT NULL,
            employee_code VARCHAR(100),
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            date_of_birth DATE,
            job_title VARCHAR(100),
            department VARCHAR(100),
            employment_type VARCHAR(50),
            address_street VARCHAR(255),
            address_city VARCHAR(255),
            address_state VARCHAR(255),
            address_zip VARCHAR(50),
            address_country VARCHAR(100),
            emergency_contact_name VARCHAR(255),
            emergency_contact_phone VARCHAR(50),
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE
        )
    ");
    
    // Tenant admins
    $conn->exec("
        CREATE TABLE IF NOT EXISTS tenant_admins (
            id VARCHAR(36) PRIMARY KEY DEFAULT gen_random_uuid()::text,
            company_id VARCHAR(36) NOT NULL,
            tenant_staff_id VARCHAR(36) NULL,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'admin',
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            mfa_enabled BOOLEAN DEFAULT FALSE,
            mfa_secret VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
            FOREIGN KEY (tenant_staff_id) REFERENCES tenant_staff(id) ON DELETE SET NULL,
            UNIQUE(company_id, username),
            UNIQUE(company_id, email)
        )
    ");
    
    // Tenant company modules
    $conn->exec("
        CREATE TABLE IF NOT EXISTS tenant_company_modules (
            id VARCHAR(36) PRIMARY KEY DEFAULT gen_random_uuid()::text,
            company_id VARCHAR(36) NOT NULL,
            module_id VARCHAR(36) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            config TEXT,
            activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            deactivated_at TIMESTAMP NULL,
            assigned_by VARCHAR(36),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES system_modules(id) ON DELETE CASCADE,
            UNIQUE(company_id, module_id)
        )
    ");
    
    // Tenant admin modules
    $conn->exec("
        CREATE TABLE IF NOT EXISTS tenant_admin_modules (
            id VARCHAR(36) PRIMARY KEY DEFAULT gen_random_uuid()::text,
            tenant_admin_id VARCHAR(36) NOT NULL,
            module_id VARCHAR(36) NOT NULL,
            granted_by VARCHAR(36),
            granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_admin_id) REFERENCES tenant_admins(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES system_modules(id) ON DELETE CASCADE,
            FOREIGN KEY (granted_by) REFERENCES tenant_admins(id),
            UNIQUE(tenant_admin_id, module_id)
        )
    ");
    
    echo "✅ Tables created/verified successfully\n\n";
    
    // Insert seed data
    echo "🌱 Inserting seed data...\n";
    
    // 1. System Modules
    echo "  - System modules...\n";
    $modules = [
        ['salg', 'Salg', 'Sales and point-of-sale module', 'bi-cart'],
        ['webmail', 'Webmail', 'Email client and management', 'bi-envelope'],
        ['arrangementer', 'Arrangementer', 'Event management and registration', 'bi-calendar-event'],
        ['medlemskap', 'Medlemskap', 'Membership management', 'bi-people'],
        ['reservasjoner', 'Reservasjoner', 'Reservation and booking system', 'bi-calendar-check'],
        ['økonomi', 'Økonomi', 'Financial management and reporting', 'bi-graph-up'],
        ['utleie', 'Utleie', 'Rental management', 'bi-key'],
        ['ditt_nettsted', 'Ditt Nettsted', 'Website configuration', 'bi-globe'],
        ['deres_firma', 'Deres Firma', 'Company profile and settings', 'bi-building']
    ];
    
    foreach ($modules as $module) {
        $stmt = $conn->prepare("
            INSERT INTO system_modules (module_key, name, description, icon, is_active, created_at)
            VALUES (?, ?, ?, ?, TRUE, CURRENT_TIMESTAMP)
            ON CONFLICT (module_key) DO NOTHING
        ");
        $stmt->execute($module);
    }
    
    // 2. Demo Company
    echo "  - Demo company (bergsoy)...\n";
    
    // Check if company already exists
    $stmt = $conn->prepare("SELECT id FROM tenant_companies WHERE subdomain = 'bergsoy' LIMIT 1");
    $stmt->execute();
    $existing = $stmt->fetch();
    
    if ($existing) {
        $companyId = $existing['id'];
        echo "    Company 'bergsoy' already exists (ID: $companyId)\n";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO tenant_companies (name, subdomain, is_active, created_at)
            VALUES ('Bergsøy Danseklubb', 'bergsoy', TRUE, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute();
        $companyId = $stmt->fetchColumn();
        echo "    Created new company 'bergsoy' (ID: $companyId)\n";
    }
    
    // 3. Demo Admin User
    echo "  - Demo admin user (admin / admin123)...\n";
    
    // Check if admin already exists
    $stmt = $conn->prepare("SELECT id FROM tenant_admins WHERE company_id = ? AND username = 'admin' LIMIT 1");
    $stmt->execute([$companyId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $adminId = $existing['id'];
        echo "    Admin 'admin' already exists (ID: $adminId)\n";
    } else {
        $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $conn->prepare("
            INSERT INTO tenant_admins (company_id, username, password_hash, is_active, created_at)
            VALUES (?, 'admin', ?, TRUE, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([$companyId, $passwordHash]);
        $adminId = $stmt->fetchColumn();
        echo "    Created new admin 'admin' (ID: $adminId)\n";
    }
    
    // 4. Assign all modules to demo company
    echo "  - Assigning modules to demo company...\n";
    $stmt = $conn->query("SELECT id FROM system_modules WHERE is_active = TRUE");
    $moduleIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($moduleIds as $moduleId) {
        $stmt = $conn->prepare("
            INSERT INTO tenant_company_modules (company_id, module_id, is_active, created_at)
            VALUES (?, ?, TRUE, CURRENT_TIMESTAMP)
            ON CONFLICT DO NOTHING
        ");
        $stmt->execute([$companyId, $moduleId]);
    }
    
    // 5. Assign all modules to demo admin
    echo "  - Assigning module access to admin...\n";
    foreach ($moduleIds as $moduleId) {
        $stmt = $conn->prepare("
            INSERT INTO tenant_admin_modules (tenant_admin_id, module_id, is_active, created_at, granted_at)
            VALUES (?, ?, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON CONFLICT DO NOTHING
        ");
        $stmt->execute([$adminId, $moduleId]);
    }
    
    // 6. Sample announcement for news stream (SSE)
    echo "  - Creating sample announcements...\n";
    $stmt = $conn->prepare("
        INSERT INTO x_tenant_announcements (company_id, title, content, announcement_type, is_published, created_at)
        VALUES (?, 'Velkommen til Salesman!', 'Systemet er nå konfigurert og klart til bruk.', 'info', 1, CURRENT_TIMESTAMP)
        ON CONFLICT DO NOTHING
    ");
    try {
        $stmt->execute([$companyId]);
    } catch (Exception $e) {
        echo "    Warning: Could not insert announcement: " . $e->getMessage() . "\n";
    }
    
    // 7. Email tables
    echo "  - Setting up email tables...\n";
    
    // Email configs table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS email_configs (
            id VARCHAR(36) PRIMARY KEY DEFAULT gen_random_uuid()::text,
            company_id VARCHAR(36) NOT NULL,
            smtp_host VARCHAR(255) NOT NULL,
            smtp_port INTEGER NOT NULL DEFAULT 587,
            smtp_encryption VARCHAR(10) DEFAULT 'tls',
            smtp_username VARCHAR(255) NOT NULL,
            smtp_password VARCHAR(255) NOT NULL,
            from_email VARCHAR(255) NOT NULL,
            from_name VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE
        )
    ");
    
    // Email accounts table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS email_accounts (
            id VARCHAR(36) PRIMARY KEY DEFAULT gen_random_uuid()::text,
            company_id VARCHAR(36) NOT NULL,
            email_address VARCHAR(255) NOT NULL,
            imap_host VARCHAR(255) NOT NULL,
            imap_port INTEGER NOT NULL DEFAULT 993,
            imap_encryption VARCHAR(10) DEFAULT 'ssl',
            imap_username VARCHAR(255) NOT NULL,
            imap_password VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            last_sync TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE,
            UNIQUE(company_id, email_address)
        )
    ");
    
    // Email queue table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS email_queue (
            id VARCHAR(36) PRIMARY KEY DEFAULT gen_random_uuid()::text,
            company_id VARCHAR(36) NOT NULL,
            to_email VARCHAR(255) NOT NULL,
            to_name VARCHAR(255),
            subject VARCHAR(500) NOT NULL,
            body TEXT NOT NULL,
            attachments TEXT,
            priority VARCHAR(20) DEFAULT 'normal',
            status VARCHAR(20) DEFAULT 'pending',
            attempts INTEGER DEFAULT 0,
            max_attempts INTEGER DEFAULT 3,
            last_attempt_at TIMESTAMP NULL,
            next_retry_at TIMESTAMP NULL,
            sent_at TIMESTAMP NULL,
            failed_at TIMESTAMP NULL,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES tenant_companies(id) ON DELETE CASCADE
        )
    ");
    
    echo "\n✅ PostgreSQL database setup completed successfully!\n\n";
    echo "🔐 Demo credentials:\n";
    echo "   Subdomain: bergsoy\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n\n";
    echo "🚀 You can now access the system at: http://bergsoy.salesman.local:5000\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
