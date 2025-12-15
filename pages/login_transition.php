<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    header('Location: /');
    exit;
}

$companyName = 'Velkommen';
$logoUrl = '/attached_assets/salesman-removebg-preview_1761762992662.png';

try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT name, logo_url FROM tenant_companies WHERE id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($company) {
        $companyName = $company['name'] ?? 'Velkommen';
        if (!empty($company['logo_url'])) {
            $logoUrl = $company['logo_url'];
        }
    }
} catch (Exception $e) {
    error_log("Login transition error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velkommen - <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow: hidden;
        }
        
        .transition-container {
            text-align: center;
            padding: 40px;
        }
        
        .logo-wrapper {
            animation: zoomPulse 1s ease-out forwards;
            margin-bottom: 20px;
        }
        
        .logo-wrapper img {
            max-height: 180px;
            width: auto;
            filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.3));
        }
        
        .company-name {
            color: white;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 0.5s forwards;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .welcome-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 18px;
            font-weight: 400;
            margin-bottom: 30px;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 0.8s forwards;
        }
        
        .loading-indicator {
            opacity: 0;
            animation: fadeIn 0.8s ease-out 1s forwards;
        }
        
        .loading-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
        }
        
        .loading-dots span {
            width: 12px;
            height: 12px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }
        
        .loading-dots span:nth-child(1) {
            animation-delay: -0.32s;
        }
        
        .loading-dots span:nth-child(2) {
            animation-delay: -0.16s;
        }
        
        .loading-dots span:nth-child(3) {
            animation-delay: 0s;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes zoomPulse {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes bounce {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }
        
        .progress-bar-wrapper {
            width: 200px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin: 20px auto 0;
            overflow: hidden;
            opacity: 0;
            animation: fadeIn 0.5s ease-out 1.2s forwards;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 2px;
            width: 0%;
            animation: progressFill 2s ease-out 1.3s forwards;
        }
        
        @keyframes progressFill {
            from {
                width: 0%;
            }
            to {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="transition-container">
        <div class="logo-wrapper">
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo">
        </div>
        
        <h1 class="company-name"><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></h1>
        
        <p class="welcome-text">
            <i class="bi bi-check-circle-fill me-2"></i>Innlogget som <?= htmlspecialchars($_SESSION['admin_username'] ?? 'bruker', ENT_QUOTES, 'UTF-8') ?>
        </p>
        
        <div class="loading-indicator">
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        
        <div class="progress-bar-wrapper">
            <div class="progress-bar-fill"></div>
        </div>
    </div>
    
    <script>
        const apiEndpoints = [
            '/api/get_economy_data.php?view=overview',
            '/api/get_events.php',
            '/api/get_terms_conditions.php',
            '/api/get_pending_registrations.php',
            '/api/get_payment_methods.php',
            '/api/get_membership_plans.php',
            '/api/get_activity_summary.php',
            '/api/get_output_products.php',
            '/api/get_members.php',
            '/api/get_sales_items.php',
            '/api/get_sales_products.php',
            '/api/get_cart.php'
        ];
        
        const preloadedData = {};
        let dataLoaded = false;
        let minTimeElapsed = false;
        
        async function preloadData() {
            const promises = apiEndpoints.map(async (endpoint) => {
                try {
                    const response = await fetch(endpoint);
                    const data = await response.json();
                    const key = endpoint.split('/').pop().split('?')[0].replace('.php', '');
                    preloadedData[key] = data;
                } catch (e) {
                    console.log('Preload error for ' + endpoint + ':', e);
                }
            });
            
            await Promise.allSettled(promises);
            sessionStorage.setItem('dashboardPreload', JSON.stringify(preloadedData));
            sessionStorage.setItem('dashboardPreloadTime', Date.now().toString());
            dataLoaded = true;
            checkRedirect();
        }
        
        function checkRedirect() {
            if (dataLoaded && minTimeElapsed) {
                window.location.href = '/pages/company_dashboard.php';
            }
        }
        
        preloadData();
        
        setTimeout(function() {
            minTimeElapsed = true;
            checkRedirect();
        }, 2500);
    </script>
</body>
</html>
