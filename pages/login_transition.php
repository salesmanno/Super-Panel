<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/company_login.php';

try {
    companySessionStart();
    $db = companyLoginDatabase();
    $pending = companyLoginIsPending();
    companyLoginRequire($db, $pending);
} catch (Throwable $e) {
    companySessionRespondFailure($e,$db??null,'login_transition');
}

$companyName = $pending ? 'Velg bedrift' : 'Velkommen';
$logoUrl = '/attached_assets/salesman-removebg-preview_1761762992662.png';
$preloadEndpoints = [];
$preloadContext = '';

if (!$pending) {
try {
    $stmt = $db->prepare("SELECT name, logo_url FROM tenant_companies WHERE id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($company) {
        $companyName = $company['name'] ?? 'Velkommen';
        if (!empty($company['logo_url'])) {
            $logoUrl = $company['logo_url'];
        }
    }
    $preloadContext = companyLoginContext();
    $moduleEndpoints = [
        'dashboard' => ['/api/get_output_products.php'],
        'economy' => ['/api/get_economy_data.php?view=overview', '/api/get_membership_plans.php'],
        'events' => [
            '/api/get_events.php',
            '/api/get_pending_registrations.php',
            '/api/get_payment_methods.php',
            '/api/get_membership_plans.php',
            '/api/get_output_products.php'
        ],
        'membership' => [
            '/api/get_membership_plans.php',
            '/api/get_members.php',
            '/api/get_output_products.php'
        ],
        'rental' => ['/api/get_output_products.php'],
        'reservation' => ['/api/get_output_products.php'],
        'sales' => [
            '/api/get_sales_items.php',
            '/api/get_sales_products.php',
            '/api/get_cart.php',
            '/api/get_payment_methods.php',
            '/api/get_output_products.php'
        ]
    ];
    $preloadEndpoints = ['/api/get_terms_conditions.php', '/api/get_activity_summary.php'];
    foreach (getTenantAdminModules($_SESSION['tenant_admin_id']) as $module) {
        $preloadEndpoints = array_merge($preloadEndpoints, $moduleEndpoints[$module['module_key']] ?? []);
    }
    $preloadEndpoints = array_values(array_unique($preloadEndpoints));
} catch (Throwable $e) {
    // Optional preload failures must not disclose authentication or database details.
}
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
            background: transparent;
            border-radius: 20px;
            padding: 15px;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.05) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            transition: width 0.3s ease-out;
        }

        body.company-choice-page { min-height: 100dvh; overflow: auto; padding: 24px 16px; }
        .company-choice { width: min(100%, 540px); padding: 30px clamp(18px, 4vw, 36px); color: #fff; }
        .company-choice .logo-wrapper img { max-height: 110px; max-width: 100%; }
        .company-choice .company-name, .company-choice .welcome-text { opacity: 1; animation: none; }
        .company-choice .welcome-text { margin-bottom: 24px; }
        .company-list { display: grid; gap: 12px; text-align: left; }
        .company-card { display: flex; align-items: center; gap: 16px; width: 100%; min-height: 86px; padding: 16px; border: 1px solid rgba(255,255,255,.45); border-radius: 14px; background: rgba(255,255,255,.13); color: #fff; text-align: left; font: inherit; cursor: pointer; }
        .company-card:hover:not(:disabled) { background: rgba(255,255,255,.23); }
        .company-card:disabled { opacity: .65; cursor: wait; }
        .company-card img { width: 54px; height: 54px; object-fit: contain; border-radius: 8px; background: rgba(255,255,255,.9); padding: 5px; flex-shrink: 0; }
        .company-card-name { flex: 1; font-weight: 600; overflow-wrap: anywhere; }
        .company-card-action { font-size: 14px; flex-shrink: 0; }
        .choice-status { margin: 20px 0 12px; color: #fff; line-height: 1.6; }
        .choice-retry { min-height: 44px; padding: 10px 24px; border: 1px solid #fff; border-radius: 8px; background: #f5f2ff; color: #443775; font-weight: 600; }
        .choice-cancel { display: inline-block; min-height: 44px; margin-top: 22px; padding: 10px 16px; color: #fff; text-underline-offset: 4px; }
        .company-choice :is(button,a):focus-visible { outline: 3px solid #ffe5a3; outline-offset: 4px; }
        .choice-skeleton { height: 86px; border-radius: 14px; background: rgba(255,255,255,.16); animation: choicePulse 1.4s ease-in-out infinite alternate; }
        @keyframes choicePulse { from { opacity: .4; } to { opacity: 1; } }
        @media (max-width: 420px) { .company-card { gap: 10px; padding: 12px; } .company-card-action { font-size: 12px; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } .company-name, .welcome-text, .loading-indicator, .progress-bar-wrapper { opacity: 1; } }
    </style>
</head>
<body<?= $pending ? ' class="company-choice-page"' : '' ?>>
    <?php if ($pending): ?>
    <main class="transition-container company-choice" id="company-choice" data-default-logo="/attached_assets/salesman-removebg-preview_1761762992662.png" aria-labelledby="choice-heading">
        <div class="logo-wrapper"><img src="/attached_assets/salesman-removebg-preview_1761762992662.png" alt="Salesman"></div>
        <h1 class="company-name" id="choice-heading">Velg bedrift</h1>
        <p class="welcome-text">Velg bedriften du vil logge inn i.</p>
        <div class="company-list" id="company-list" aria-busy="true">
            <div class="choice-skeleton" aria-hidden="true"></div>
            <div class="choice-skeleton" aria-hidden="true"></div>
        </div>
        <p class="choice-status" id="choice-status" role="status" aria-live="polite">Henter tilgjengelige bedrifter …</p>
        <button class="choice-retry" id="choice-retry" type="button" hidden>Prøv igjen</button>
        <noscript><p>Aktiver JavaScript for å velge bedrift.</p></noscript>
        <a class="choice-cancel" href="/auth/logout.php">Avbryt og logg ut</a>
    </main>
    <script src="/assets/company_choice.js"></script>
    <?php else: ?>
    <div class="transition-container">
        <div class="logo-wrapper">
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" onerror="this.onerror=null;this.src='/attached_assets/salesman-removebg-preview_1761762992662.png'">
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
        const apiEndpoints = <?= json_encode($preloadEndpoints, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const preloadContext = <?= json_encode($preloadContext, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        const preloadedData = {};
        let completed = 0;
        const total = apiEndpoints.length;
        const progressFill = document.querySelector('.progress-bar-fill');
        let redirected = false;

        function setProgress(pct) {
            if (progressFill) progressFill.style.width = pct + '%';
        }

        function doRedirect() {
            if (redirected) return;
            redirected = true;
            try {
                if (preloadContext) {
                    sessionStorage.setItem('dashboardPreloadContext', preloadContext);
                    sessionStorage.setItem('dashboardPreload', JSON.stringify(preloadedData));
                    sessionStorage.setItem('dashboardPreloadTime', Date.now().toString());
                }
            } catch (e) { /* Storage may be disabled by the browser. */ }
            window.location.href = '/pages/company_dashboard.php';
        }

        async function preloadData() {
            const promises = apiEndpoints.map(async (endpoint) => {
                try {
                    const response = await fetch(endpoint, { credentials: 'same-origin' });
                    if (!response.ok || redirected) return;
                    const data = await response.json();
                    if (data.success === false || redirected) return;
                    const key = endpoint.split('/').pop().split('?')[0].replace('.php', '');
                    preloadedData[key] = data;
                } catch (e) {
                    // The dashboard can retry unavailable module data.
                } finally {
                    completed++;
                    setProgress(total ? Math.round((completed / total) * 100) : 100);
                }
            });

            await Promise.allSettled(promises);
            doRedirect();
        }

        const hardTimeout = setTimeout(doRedirect, 6000);

        preloadData().then(() => clearTimeout(hardTimeout));
    </script>
    <?php endif; ?>
</body>
</html>
