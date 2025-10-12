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
            max-width: 500px;
            width: 100%;
            display: block;
        }
        
        @media (max-width: 992px) {
            .login-container {
                grid-template-columns: 1fr;
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
                height: 120px;
            }
            .login-section {
                padding: 30px 20px;
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
            height: 160px;
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
        
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
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
                <img src="/attached_assets/salesman-removebg-preview_1760299160728.png" alt="Logo">
                <h1>SuperUser Portal</h1>
                <p>Bedriftsportal - Logg inn</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
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
                    Har du mistet passordet ditt?
                </button>
            </form>
        </div>
    </div>
    
    <script>
        let dbCheckInterval = null;
        let isForgotPasswordMode = false;
        
        document.addEventListener('DOMContentLoaded', function() {
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
        
        window.addEventListener('beforeunload', function() {
            if (dbCheckInterval) {
                clearInterval(dbCheckInterval);
            }
        });
    </script>
</body>
</html>
