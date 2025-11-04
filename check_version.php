<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Versjon-sjekk</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .version-box { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #10b981; font-size: 24px; font-weight: bold; }
        .error { color: #ef4444; font-size: 24px; font-weight: bold; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="version-box">
        <h1>📋 Versjon-sjekk</h1>
        
        <?php
        $dashboardFile = 'pages/company_dashboard.php';
        
        if (!file_exists($dashboardFile)) {
            echo '<p class="error">❌ Filen pages/company_dashboard.php finnes ikke!</p>';
            exit;
        }
        
        $content = file_get_contents($dashboardFile);
        
        // Sjekk om den nye koden finnes
        $hasNewCode = strpos($content, 'CRITICAL: Define these functions FIRST before anything else') !== false;
        $hasFunctionAtTop = strpos($content, 'function navigateToDittNettsted()') !== false;
        
        if ($hasNewCode && $hasFunctionAtTop) {
            echo '<p class="success">✅ RIKTIG VERSJON!</p>';
            echo '<p>Filen er oppdatert med fiksen. Navigasjon skal fungere.</p>';
            echo '<p><strong>Hvis tabs fortsatt ikke fungerer:</strong></p>';
            echo '<ol>';
            echo '<li>Trykk <code>Ctrl + Shift + R</code> for å tømme cache</li>';
            echo '<li>Eller trykk F12 → høyreklikk på oppdater-knappen → "Empty Cache and Hard Reload"</li>';
            echo '</ol>';
        } else {
            echo '<p class="error">❌ GAMMEL VERSJON!</p>';
            echo '<p>Filen er IKKE oppdatert ennå.</p>';
            echo '<p><strong>Hva du må gjøre:</strong></p>';
            echo '<ol>';
            echo '<li>Last ned <code>pages/company_dashboard.php</code> fra Replit</li>';
            echo '<li>Last opp filen til super.salesman.no (erstatt den gamle)</li>';
            echo '<li>Oppdater denne siden</li>';
            echo '</ol>';
        }
        ?>
        
        <hr style="margin: 30px 0;">
        <p><a href="pages/company_dashboard.php" style="color: #667eea; text-decoration: none; font-weight: bold;">→ Gå til Dashboard</a></p>
    </div>
</body>
</html>
