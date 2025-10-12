<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_credentials':
            $error_message = 'Ugyldig brukernavn eller passord.';
            break;
        case 'system_error':
            $error_message = 'Systemfeil. Vennligst prøv igjen senere.';
            break;
        case 'account_inactive':
            $error_message = 'Kontoen din er inaktiv. Kontakt administrator.';
            break;
        case 'company_inactive':
            $error_message = 'Firmaet er deaktivert. Kontakt support for assistanse.';
            break;
        default:
            $error_message = 'En feil oppstod. Vennligst prøv igjen.';
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bedriftsportal - Innlogging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-start: #667eea;
            --primary-end: #764ba2;
            --shadow-light: rgba(102, 126, 234, 0.15);
            --shadow-medium: rgba(102, 126, 234, 0.25);
            --shadow-strong: rgba(102, 126, 234, 0.35);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            padding: 20px;
            transition: background 0.3s ease;
        }
        
        body.db-error {
            background: #6b7280;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 1100px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        
        @media (max-width: 992px) {
            .login-container {
                grid-template-columns: 1fr;
            }
            .news-section {
                display: block;
                max-height: 300px;
                padding: 30px 25px;
            }
            .login-section {
                padding: 40px 25px;
            }
        }
        
        @media (max-width: 576px) {
            .login-header h1 {
                font-size: 24px;
            }
            .login-header img {
                height: 60px;
            }
            .login-section {
                padding: 30px 20px;
            }
            .news-section {
                padding: 20px 20px;
            }
        }
        
        .login-section {
            padding: 60px 50px;
            position: relative;
            z-index: 1;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header img {
            height: 80px;
            width: auto;
            margin-bottom: 24px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-start), var(--primary-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .login-header p {
            color: #6b7280;
            font-size: 15px;
            font-weight: 400;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating > .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            font-size: 15px;
            transition: all 0.2s ease;
            height: 58px;
        }
        
        .form-floating > .form-control:focus {
            border-color: var(--primary-start);
            box-shadow: 0 0 0 4px var(--shadow-light);
            outline: none;
        }
        
        .form-floating > label {
            padding: 1rem 1.25rem;
            color: #9ca3af;
            font-weight: 500;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--primary-start);
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
            border: none;
            padding: 16px 24px;
            font-weight: 600;
            font-size: 16px;
            border-radius: 12px;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 12px var(--shadow-medium);
            position: relative;
            z-index: 10;
            cursor: pointer;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px var(--shadow-strong);
            background: linear-gradient(135deg, #7690ff 0%, #8556bb 100%);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            padding: 14px 18px;
            margin-bottom: 24px;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
        }
        
        .news-section {
            background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
            padding: 60px 50px;
            color: white;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 0;
        }
        
        .news-header {
            margin-bottom: 32px;
        }
        
        .news-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }
        
        .news-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .news-content {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .news-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .news-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        
        .news-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .news-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .news-item {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .news-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(4px);
        }
        
        .news-item-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
            letter-spacing: -0.2px;
        }
        
        .news-item-content {
            font-size: 14px;
            line-height: 1.6;
            opacity: 0.95;
            margin-bottom: 12px;
        }
        
        .news-item-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            opacity: 0.8;
            font-weight: 500;
        }
        
        .news-loading {
            text-align: center;
            padding: 40px 20px;
            opacity: 0.9;
        }
        
        .spinner-border {
            width: 2rem;
            height: 2rem;
            border-width: 3px;
        }
        
        .news-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .news-badge.critical {
            background: rgba(239, 68, 68, 0.9);
        }
        
        .news-badge.high {
            background: rgba(251, 191, 36, 0.9);
        }
        
        .news-badge.medium {
            background: rgba(59, 130, 246, 0.9);
        }
        
        .news-badge.low {
            background: rgba(16, 185, 129, 0.8);
        }
        
        .news-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.25);
            text-transform: capitalize;
        }
        
        .forgot-password-link {
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
            color: var(--primary-start);
            background: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 0;
            width: 100%;
        }
        
        .forgot-password-link:hover,
        .forgot-password-link:focus {
            color: var(--primary-end);
            text-decoration: underline;
            outline: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-section">
            <div class="login-header">
                <img src="/attached_assets/26617BFC-F76D-481A-88CE-14CD3D5BBB66_1755491554819.png" alt="Logo">
                <h1>Salesman.no</h1>
                <p>Bedriftsportal - Logg inn</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/auth/company_auth.php" id="login-form">
                <div class="form-floating" id="username-field">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Brukernavn" required autofocus autocomplete="username">
                    <label for="username">Brukernavn</label>
                </div>
                
                <div class="form-floating" id="password-field">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Passord" required autocomplete="current-password">
                    <label for="password">Passord</label>
                </div>
                
                <div class="form-floating d-none" id="email-field">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="E-postadresse" autocomplete="email">
                    <label for="email">E-postadresse</label>
                </div>
                
                <div class="form-floating d-none" id="secret-field">
                    <input type="text" class="form-control" id="secret" name="secret" 
                           placeholder="Hemmelig kode">
                    <label for="secret">Hemmelig kode</label>
                </div>
                
                <button class="btn btn-primary btn-login w-100" type="submit" id="login-button">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Logg inn
                </button>
                
                <button type="button" class="forgot-password-link" id="toggle-mode">
                    Har du mistet passordet ditt?
                </button>
            </form>
        </div>
        
        <div class="news-section">
            <div class="news-header">
                <h2><i class="bi bi-newspaper me-2"></i>Siste nyheter</h2>
                <p>Hold deg oppdatert med de nyeste endringene</p>
            </div>
            
            <div class="news-content" id="news-content">
                <div class="news-loading">
                    <div class="spinner-border text-light mb-3" role="status">
                        <span class="visually-hidden">Laster nyheter...</span>
                    </div>
                    <p>Laster siste nyheter...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let eventSource = null;
        let reconnectTimeout = null;
        let dbCheckInterval = null;
        
        let isForgotPasswordMode = false;
        
        document.addEventListener('DOMContentLoaded', function() {
            connectToNewsStream();
            checkDatabaseStatus();
            // Check database status every 10 seconds
            dbCheckInterval = setInterval(checkDatabaseStatus, 10000);
            
            // Toggle between login and forgot password mode
            document.getElementById('toggle-mode').addEventListener('click', toggleMode);
        });
        
        function toggleMode() {
            isForgotPasswordMode = !isForgotPasswordMode;
            
            const usernameField = document.getElementById('username-field');
            const passwordField = document.getElementById('password-field');
            const emailField = document.getElementById('email-field');
            const secretField = document.getElementById('secret-field');
            const loginButton = document.getElementById('login-button');
            const toggleText = document.getElementById('toggle-mode');
            const loginForm = document.getElementById('login-form');
            
            if (isForgotPasswordMode) {
                // Switch to forgot password mode
                usernameField.classList.add('d-none');
                passwordField.classList.add('d-none');
                emailField.classList.remove('d-none');
                secretField.classList.remove('d-none');
                
                // Update button
                loginButton.innerHTML = '<i class="bi bi-envelope-paper me-2"></i>Send forespørsel';
                
                // Update toggle text
                toggleText.textContent = 'Til innlogging';
                
                // TODO: Change form action when forgot password endpoint is ready
                // loginForm.action = '/auth/forgot_password.php';
                
                // Update required fields
                document.getElementById('username').removeAttribute('required');
                document.getElementById('password').removeAttribute('required');
                document.getElementById('email').setAttribute('required', 'required');
                document.getElementById('secret').setAttribute('required', 'required');
                
                // Focus on email field
                document.getElementById('email').focus();
            } else {
                // Switch back to login mode
                usernameField.classList.remove('d-none');
                passwordField.classList.remove('d-none');
                emailField.classList.add('d-none');
                secretField.classList.add('d-none');
                
                // Update button
                loginButton.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Logg inn';
                
                // Update toggle text
                toggleText.textContent = 'Har du mistet passordet ditt?';
                
                // Form action stays as company_auth.php
                
                // Update required fields
                document.getElementById('username').setAttribute('required', 'required');
                document.getElementById('password').setAttribute('required', 'required');
                document.getElementById('email').removeAttribute('required');
                document.getElementById('secret').removeAttribute('required');
                
                // Focus on username field
                document.getElementById('username').focus();
            }
        }
        
        function connectToNewsStream() {
            if (eventSource) {
                eventSource.close();
            }
            
            eventSource = new EventSource('/api/news_stream.php');
            
            eventSource.onopen = function() {
                console.log('SSE connection established');
                if (reconnectTimeout) {
                    clearTimeout(reconnectTimeout);
                    reconnectTimeout = null;
                }
            };
            
            eventSource.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    updateNewsDisplay(data);
                } catch (error) {
                    console.error('Error parsing SSE data:', error);
                }
            };
            
            eventSource.onerror = function(error) {
                console.error('SSE error:', error);
                eventSource.close();
                
                document.getElementById('news-content').innerHTML = `
                    <div class="news-loading">
                        <i class="bi bi-exclamation-triangle fs-2 mb-3 d-block"></i>
                        <p>Mistet tilkobling. Prøver å koble til igjen...</p>
                    </div>
                `;
                
                reconnectTimeout = setTimeout(connectToNewsStream, 5000);
            };
        }
        
        function updateNewsDisplay(data) {
            const newsContent = document.getElementById('news-content');
            
            if (data.success && data.news && data.news.length > 0) {
                let newsHtml = '';
                
                data.news.forEach(item => {
                    const severityBadge = `<span class="news-badge ${item.severity}">${item.priority_text}</span>`;
                    
                    const newsTypeIcon = getNewsTypeIcon(item.news_type);
                    const newsTypeLabel = getNewsTypeLabel(item.news_type);
                    
                    newsHtml += `
                        <div class="news-item">
                            <div class="news-item-title">
                                ${escapeHtml(item.title)}
                            </div>
                            <div class="news-item-content">
                                ${escapeHtml(item.content)}
                            </div>
                            <div class="news-item-meta">
                                <div>
                                    <span class="news-type-badge">
                                        <i class="${newsTypeIcon}"></i>
                                        ${newsTypeLabel}
                                    </span>
                                    <span class="ms-2"><i class="bi bi-calendar3 me-1"></i>${item.formatted_date}</span>
                                </div>
                                ${severityBadge}
                            </div>
                        </div>
                    `;
                });
                
                newsContent.innerHTML = newsHtml;
            } else {
                newsContent.innerHTML = `
                    <div class="news-loading">
                        <i class="bi bi-info-circle fs-2 mb-3 d-block"></i>
                        <p>Ingen nyheter tilgjengelig</p>
                    </div>
                `;
            }
        }
        
        function checkDatabaseStatus() {
            const loginButton = document.getElementById('login-button');
            
            fetch('/api/check_database_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'connected') {
                        document.body.classList.remove('db-error');
                        if (loginButton) {
                            loginButton.disabled = false;
                        }
                    } else {
                        document.body.classList.add('db-error');
                        if (loginButton) {
                            loginButton.disabled = true;
                        }
                    }
                })
                .catch(error => {
                    console.error('Database check failed:', error);
                    document.body.classList.add('db-error');
                    if (loginButton) {
                        loginButton.disabled = true;
                    }
                });
        }
        
        window.addEventListener('beforeunload', function() {
            if (eventSource) {
                eventSource.close();
            }
            if (reconnectTimeout) {
                clearTimeout(reconnectTimeout);
            }
            if (dbCheckInterval) {
                clearInterval(dbCheckInterval);
            }
        });
        
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        function getNewsTypeIcon(type) {
            const icons = {
                'system': 'bi bi-gear',
                'feature': 'bi bi-star',
                'maintenance': 'bi bi-tools',
                'update': 'bi bi-arrow-up-circle',
                'security': 'bi bi-shield-check',
                'announcement': 'bi bi-megaphone',
                'bug': 'bi bi-bug'
            };
            return icons[type] || 'bi bi-info-circle';
        }
        
        function getNewsTypeLabel(type) {
            const labels = {
                'system': 'System',
                'feature': 'Ny funksjon',
                'maintenance': 'Vedlikehold',
                'update': 'Oppdatering',
                'security': 'Sikkerhet',
                'announcement': 'Kunngjøring',
                'bug': 'Feilretting'
            };
            return labels[type] || type;
        }
    </script>
</body>
</html>
