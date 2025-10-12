<?php
require_once 'includes/functions.php';

try {
    $db = getDB();
    
    // Create homepage_templates table to store exact HTML content that matches previews
    $sql = "CREATE TABLE IF NOT EXISTS homepage_templates (
        template_id INT AUTO_INCREMENT PRIMARY KEY,
        template_name VARCHAR(100) NOT NULL,
        template_slug VARCHAR(50) NOT NULL UNIQUE,
        description TEXT,
        html_content LONGTEXT NOT NULL,
        css_content TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql);
    echo "✓ Created homepage_templates table\n";
    
    // Create company_homepage_settings table to track which template each company uses
    $sql = "CREATE TABLE IF NOT EXISTS company_homepage_settings (
        setting_id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        selected_template_id INT,
        company_name VARCHAR(255),
        company_logo VARCHAR(255),
        custom_settings JSON,
        is_published TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(company_id),
        FOREIGN KEY (selected_template_id) REFERENCES homepage_templates(template_id)
    )";
    
    $db->exec($sql);
    echo "✓ Created company_homepage_settings table\n";
    
    // Insert the exact HTML templates that match the preview designs
    $templates = [
        [
            'name' => 'Corporate Professional',
            'slug' => 'corporate',
            'description' => 'Profesjonell bedrifts-layout med blå gradienter og header',
            'html' => '<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{company_name}}</title>
    <style>
        body { margin: 0; font-family: "Segoe UI", sans-serif; }
        .header { 
            background: linear-gradient(135deg, #0066cc 0%, #004499 100%); 
            color: white; 
            padding: 20px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .logo-section { display: flex; align-items: center; }
        .logo-section img { height: 40px; width: auto; filter: brightness(0) invert(1); }
        .logo-section span { margin-left: 12px; font-weight: bold; font-size: 24px; }
        .nav { display: flex; gap: 30px; margin-left: 60px; }
        .nav a { color: white; text-decoration: none; font-size: 16px; }
        .content { padding: 40px; max-width: 1200px; margin: 0 auto; }
        .hero { background: #f8f9fa; padding: 40px; border-radius: 8px; margin-bottom: 40px; text-align: center; }
        .services { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px; }
        .service-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .footer { background: #333; color: white; padding: 40px; text-align: center; }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="{{company_logo}}" alt="{{company_name}} Logo">
            <span>{{company_name}}</span>
        </div>
        <nav class="nav">
            <a href="#tjenester">Tjenester</a>
            <a href="#om-oss">Om oss</a>
            <a href="#kontakt">Kontakt</a>
        </nav>
    </header>
    
    <main class="content">
        <section class="hero">
            <h1>Velkommen til {{company_name}}</h1>
            <p>Profesjonelle tjenester for moderne bedrifter</p>
        </section>
        
        <section class="services" id="tjenester">
            <div class="service-card">
                <h3>Kvalitetstjenester</h3>
                <p>Vi leverer førsteklasses tjenester tilpasset deres behov.</p>
            </div>
            <div class="service-card">
                <h3>Kundesupport</h3>
                <p>Vårt team er tilgjengelig for å hjelpe deg når du trenger det.</p>
            </div>
            <div class="service-card">
                <h3>Innovasjon</h3>
                <p>Vi bruker den nyeste teknologien for å levere beste resultater.</p>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <p>&copy; 2024 {{company_name}} • Kvalitet du kan stole på</p>
    </footer>
</body>
</html>'
        ],
        [
            'name' => 'Creative Agency',
            'slug' => 'creative',
            'description' => 'Kreativ asymmetrisk design med fargerike gradienter',
            'html' => '<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{company_name}}</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; overflow-x: hidden; }
        .header { 
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4); 
            padding: 20px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
        }
        .logo-section { display: flex; align-items: center; }
        .logo-section img { height: 40px; width: auto; filter: brightness(0) invert(1); }
        .logo-section span { margin-left: 12px; font-weight: bold; font-size: 24px; color: white; }
        .nav { display: flex; gap: 25px; margin-left: 60px; }
        .nav a { color: white; text-decoration: none; font-size: 16px; }
        .hero { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 80px 40px; text-align: center; }
        .content { padding: 40px; max-width: 1200px; margin: 0 auto; }
        .portfolio { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 40px 0; }
        .portfolio-item { 
            background: linear-gradient(45deg, #f093fb, #f5576c); 
            padding: 30px; 
            border-radius: 15px; 
            color: white; 
            text-align: center;
            transform: rotate(-2deg);
        }
        .portfolio-item:nth-child(even) { transform: rotate(2deg); }
        .footer { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 40px; text-align: center; }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="{{company_logo}}" alt="{{company_name}} Logo">
            <span>{{company_name}}</span>
        </div>
        <nav class="nav">
            <a href="#portfolio">Portfolio</a>
            <a href="#tjenester">Tjenester</a>
            <a href="#kontakt">Kontakt</a>
        </nav>
    </header>
    
    <section class="hero">
        <h1>Kreativ {{company_name}}</h1>
        <p>Vi skaper unike løsninger som skiller seg ut</p>
    </section>
    
    <main class="content">
        <section class="portfolio" id="portfolio">
            <div class="portfolio-item">
                <h3>Design</h3>
                <p>Kreative konsepter</p>
            </div>
            <div class="portfolio-item">
                <h3>Utvikling</h3>
                <p>Moderne teknologi</p>
            </div>
            <div class="portfolio-item">
                <h3>Strategi</h3>
                <p>Innovative løsninger</p>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <p>&copy; 2024 {{company_name}} • Kreativitet i fokus</p>
    </footer>
</body>
</html>'
        ],
        [
            'name' => 'Modern Minimal',
            'slug' => 'minimal',
            'description' => 'Ren og minimal design med mye hvitt rom',
            'html' => '<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{company_name}}</title>
    <style>
        body { margin: 0; font-family: "Helvetica Neue", sans-serif; color: #333; line-height: 1.6; }
        .header { 
            padding: 30px; 
            text-align: center; 
            border-bottom: 1px solid #f0f0f0;
            background: white;
        }
        .logo-section { display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .logo-section img { height: 48px; width: auto; }
        .logo-section span { margin-left: 16px; font-weight: 600; font-size: 28px; color: #333; }
        .nav { display: flex; gap: 40px; justify-content: center; margin-top: 30px; }
        .nav a { color: #666; text-decoration: none; font-size: 16px; }
        .content { padding: 80px 40px; max-width: 800px; margin: 0 auto; text-align: center; }
        .section { margin: 80px 0; }
        .section h2 { font-size: 36px; margin-bottom: 20px; font-weight: 300; }
        .section p { font-size: 18px; color: #666; margin-bottom: 40px; }
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; margin: 60px 0; }
        .feature { text-align: center; }
        .feature-number { 
            width: 60px; 
            height: 60px; 
            border-radius: 50%; 
            background: #f8f9fa; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0 auto 20px; 
            font-weight: bold; 
            font-size: 24px; 
        }
        .footer { background: #f8f9fa; padding: 60px 40px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="{{company_logo}}" alt="{{company_name}} Logo">
            <span>{{company_name}}</span>
        </div>
        <nav class="nav">
            <a href="#tjenester">Tjenester</a>
            <a href="#om-oss">Om oss</a>
            <a href="#kontakt">Kontakt</a>
        </nav>
    </header>
    
    <main class="content">
        <section class="section">
            <h1>{{company_name}}</h1>
            <p>Enkel eleganse møter funksjonalitet</p>
        </section>
        
        <section class="features" id="tjenester">
            <div class="feature">
                <div class="feature-number">1</div>
                <h3>Kvalitet</h3>
                <p>Høyeste standard</p>
            </div>
            <div class="feature">
                <div class="feature-number">2</div>
                <h3>Enkelhet</h3>
                <p>Rett på sak</p>
            </div>
            <div class="feature">
                <div class="feature-number">3</div>
                <h3>Pålitelighet</h3>
                <p>Du kan stole på oss</p>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <p>&copy; 2024 {{company_name}} • Enkelt og elegant</p>
    </footer>
</body>
</html>'
        ],
        [
            'name' => 'Modern Tech',
            'slug' => 'modern',
            'description' => 'Moderne layout med lilla gradienter og teknologi-fokus',
            'html' => '<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{company_name}}</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f8f9fa; }
        .header { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            padding: 20px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
        }
        .logo-section { display: flex; align-items: center; }
        .logo-section img { height: 40px; width: auto; filter: brightness(0) invert(1); }
        .logo-section span { margin-left: 12px; font-weight: bold; font-size: 24px; color: white; }
        .nav { display: flex; gap: 30px; margin-left: 60px; }
        .nav a { color: white; text-decoration: none; font-size: 16px; }
        .hero { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            padding: 80px 40px; 
            text-align: center;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }
        .content { padding: 40px; max-width: 1200px; margin: 0 auto; }
        .tech-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin: 40px 0; }
        .tech-card { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(102,126,234,0.1);
            border: 1px solid rgba(102,126,234,0.2);
        }
        .tech-badge { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            padding: 8px 16px; 
            border-radius: 20px; 
            font-size: 12px; 
            display: inline-block; 
            margin-bottom: 15px;
        }
        .progress-bar { 
            background: #e9ecef; 
            height: 8px; 
            border-radius: 4px; 
            overflow: hidden; 
            margin: 15px 0;
        }
        .progress-fill { 
            background: linear-gradient(90deg, #667eea, #764ba2); 
            height: 100%; 
            border-radius: 4px;
        }
        .footer { background: #333; color: white; padding: 40px; text-align: center; }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="{{company_logo}}" alt="{{company_name}} Logo">
            <span>{{company_name}}</span>
        </div>
        <nav class="nav">
            <a href="#teknologi">Teknologi</a>
            <a href="#tjenester">Tjenester</a>
            <a href="#kontakt">Kontakt</a>
        </nav>
    </header>
    
    <section class="hero">
        <h1>{{company_name}}</h1>
        <p>Moderne teknologi for fremtidens bedrifter</p>
    </section>
    
    <main class="content">
        <section class="tech-grid" id="teknologi">
            <div class="tech-card">
                <div class="tech-badge">TEKNOLOGI</div>
                <h3>Cutting-edge</h3>
                <p>Innovative løsninger som driver bedriften fremover</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 90%;"></div>
                </div>
            </div>
            <div class="tech-card">
                <div class="tech-badge">DESIGN</div>
                <h3>Brukersentrert</h3>
                <p>Intuitive grensesnitt som brukerne elsker</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 85%;"></div>
                </div>
            </div>
            <div class="tech-card">
                <div class="tech-badge">SUPPORT</div>
                <h3>24/7 Service</h3>
                <p>Alltid tilgjengelig når du trenger oss</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 95%;"></div>
                </div>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <p>&copy; 2024 {{company_name}} • Teknologi som forandrer alt</p>
    </footer>
</body>
</html>'
        ],
        [
            'name' => 'Classic Business',
            'slug' => 'classic',
            'description' => 'Klassisk web-design med tradisjonell struktur',
            'html' => '<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{company_name}}</title>
    <style>
        body { margin: 0; font-family: "Georgia", serif; color: #333; }
        .header { 
            background: #8b4513; 
            color: white; 
            padding: 20px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
        }
        .logo-section { display: flex; align-items: center; }
        .logo-section img { height: 40px; width: auto; filter: brightness(0) invert(1); }
        .logo-section span { margin-left: 12px; font-weight: bold; font-size: 24px; }
        .nav { display: flex; gap: 24px; margin-left: 60px; }
        .nav a { color: white; text-decoration: none; font-size: 16px; }
        .hero { 
            background: #faf5f0; 
            padding: 60px 40px; 
            text-align: center; 
            border: 2px solid #d4a574;
            margin: 0;
        }
        .hero h1 { color: #8b4513; margin-bottom: 10px; }
        .hero p { color: #666; font-size: 18px; }
        .content { padding: 40px; max-width: 1200px; margin: 0 auto; }
        .values-section { display: flex; gap: 40px; margin: 40px 0; }
        .values-content { flex: 2; }
        .values-content h2 { color: #8b4513; border-bottom: 3px solid #d4a574; padding-bottom: 10px; }
        .value-bar { 
            height: 4px; 
            background: #d4a574; 
            margin: 8px 0; 
            border-radius: 2px;
        }
        .value-bar:nth-child(2) { width: 85%; background: #e5d4c1; }
        .value-bar:nth-child(3) { width: 70%; background: #e5d4c1; }
        .contact-box { 
            flex: 1; 
            background: #faf5f0; 
            padding: 30px; 
            border-radius: 8px; 
            border: 2px solid #e5d4c1;
        }
        .contact-box h3 { color: #8b4513; margin-bottom: 15px; }
        .contact-line { 
            height: 2px; 
            background: #d4a574; 
            margin: 8px 0;
        }
        .footer { 
            background: #f5f0e8; 
            padding: 30px; 
            text-align: center; 
            color: #8b4513; 
            border-top: 2px solid #d4a574;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="{{company_logo}}" alt="{{company_name}} Logo">
            <span>{{company_name}}</span>
        </div>
        <nav class="nav">
            <a href="#om-oss">Om oss</a>
            <a href="#tjenester">Tjenester</a>
            <a href="#historie">Historie</a>
            <a href="#kontakt">Kontakt</a>
        </nav>
    </header>
    
    <section class="hero">
        <h1>Velkommen til {{company_name}}</h1>
        <p>Tradisjon møter innovasjon • Etablert 1924 • Pålitelig kvalitet gjennom generasjoner</p>
    </section>
    
    <main class="content">
        <section class="values-section">
            <div class="values-content">
                <h2>Våre verdier</h2>
                <p>Siden 1924 har vi bygget vår virksomhet på solide verdier og utmerket håndverk.</p>
                <div class="value-bar"></div>
                <div class="value-bar"></div>
                <div class="value-bar"></div>
            </div>
            <div class="contact-box">
                <h3>Kontakt oss</h3>
                <p>Ta kontakt for en uforpliktende samtale om hvordan vi kan hjelpe.</p>
                <div class="contact-line"></div>
                <div class="contact-line"></div>
                <div class="contact-line"></div>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <p>&copy; 2024 {{company_name}} • Kvalitet siden 1924</p>
    </footer>
</body>
</html>'
        ]
    ];
    
    // Insert templates
    $stmt = $db->prepare("
        INSERT INTO homepage_templates (template_name, template_slug, description, html_content) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        template_name = VALUES(template_name),
        description = VALUES(description),
        html_content = VALUES(html_content),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    foreach ($templates as $template) {
        $stmt->execute([
            $template['name'],
            $template['slug'],
            $template['description'],
            $template['html']
        ]);
        echo "✓ Inserted/Updated template: {$template['name']}\n";
    }
    
    // Set default template for company (Minimal as requested)
    $stmt = $db->prepare("
        INSERT INTO company_homepage_settings (company_id, selected_template_id, company_name, company_logo) 
        SELECT 1, t.template_id, c.name, '/attached_assets/F63990AB-E2D7-44E7-95C8-8FD61C152338_1755512564540.png'
        FROM homepage_templates t, companies c
        WHERE t.template_slug = 'minimal' AND c.company_id = 1
        ON DUPLICATE KEY UPDATE 
        selected_template_id = VALUES(selected_template_id),
        company_name = VALUES(company_name),
        company_logo = VALUES(company_logo),
        updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute();
    echo "✓ Set Minimal template as default for company\n";
    
    echo "\n🎉 Homepage template system setup complete!\n";
    echo "All templates now contain exact HTML that matches the preview designs.\n";
    echo "When public homepage is implemented, it will look identical to previews.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>