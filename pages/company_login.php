<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_message = '';
$success_message = '';

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
        case 'database_unavailable':
            $error_message = 'Databasen er ikke tilgjengelig. Vennligst prøv igjen senere.';
            break;
        case 'already_logged_in':
            $error_message = 'Brukeren er allerede innlogget. Uautorisert bruk? Ta kontakt med 404 69 364.';
            break;
        case 'session_expired':
            $error_message = 'Økten din har utløpt. Vennligst logg inn på nytt.';
            break;
        case 'session_error':
            $error_message = 'Det oppstod en feil med økten din. Vennligst logg inn på nytt.';
            break;
        case 'database_changed':
            $error_message = 'Databasekonfigurasjonen ble endret. Vennligst logg inn på nytt.';
            break;
        case 'not_authenticated':
            $error_message = 'Du må logge inn for å få tilgang.';
            break;
        default:
            $error_message = 'En feil oppstod. Vennligst prøv igjen.';
    }
}

if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'reset_request_sent':
            $success_message = 'Din forespørsel er mottatt og behandles.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Salesman Portal">
    <title>Bedriftsportal - Innlogging</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/attached_assets/salesman-removebg-preview_1761762992662.png">
    <link rel="icon" type="image/png" href="/attached_assets/salesman-removebg-preview_1761762992662.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/pages/company_login.css">
</head>
<body>
    <main class="login-container">
        <section class="login-section" aria-labelledby="login-title">
            <div class="login-header">
                <img src="/attached_assets/salesman-removebg-preview_1761762992662.png" alt="Salesman.no">
                <p class="portal-kicker">BEDRIFTSPORTAL</p>
                <h1 id="login-title">SuperUser Portal</h1>
                <p>Bedriftsportal - Logg inn</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert" aria-live="assertive">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success d-flex align-items-center" role="status">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?></div>
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
                    Innloggingsproblemer?
                </button>
            </form>
        </section>
        
        <aside class="news-section" aria-labelledby="news-title">
            <div class="news-header">
                <p class="news-kicker">FRA SALESMAN.NO</p>
                <h2 id="news-title"><i class="bi bi-newspaper me-2" aria-hidden="true"></i>Siste nyheter</h2>
            </div>
            
            <div class="news-content" id="news-content" aria-live="polite">
                <div class="news-loading">
                    <div class="spinner-border text-light mb-3" role="status">
                        <span class="visually-hidden">Laster nyheter...</span>
                    </div>
                    <p>Laster siste nyheter...</p>
                </div>
            </div>
        </aside>
    </main>
    
    <script>
        let eventSource = null;
        let reconnectTimeout = null;
        let dbCheckInterval = null;
        let isForgotPasswordMode = false;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Load news via fetch instead of SSE (works better with PHP built-in server)
            loadNews();
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
                
                // Change form action for forgot password
                loginForm.action = '/auth/forgot_password.php';
                
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
                
                // Change form action back to login
                loginForm.action = '/auth/company_auth.php';
                
                // Update required fields
                document.getElementById('username').setAttribute('required', 'required');
                document.getElementById('password').setAttribute('required', 'required');
                document.getElementById('email').removeAttribute('required');
                document.getElementById('secret').removeAttribute('required');
                
                // Focus on username field
                document.getElementById('username').focus();
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
        
        function loadNews() {
            fetch('/api/get_news.php')
                .then(response => response.json())
                .then(data => {
                    updateNewsDisplay(data);
                })
                .catch(error => {
                    console.error('Error loading news:', error);
                    document.getElementById('news-content').innerHTML = `
                        <div class="news-loading">
                            <i class="bi bi-exclamation-triangle fs-2 mb-3 d-block"></i>
                            <p>Kunne ikke laste nyheter. Prøv igjen senere.</p>
                        </div>
                    `;
                });
        }
        
        function updateNewsDisplay(data) {
            const newsContent = document.getElementById('news-content');
            
            if (data.success && data.news && data.news.length > 0) {
                let newsHtml = '';
                
                data.news.forEach(item => {
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
