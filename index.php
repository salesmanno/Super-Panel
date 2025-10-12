<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['super_user_id']) && isset($_SESSION['company_id'])) {
    header('Location: pages/company_dashboard.php');
    exit;
}

// Handle error messages
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_credentials':
            $error_message = 'Ugyldig brukernavn eller passord.';
            break;
        case 'system_error':
            $error_message = 'Systemfeil. Prøv igjen senere.';
            break;
        case 'account_inactive':
            $error_message = 'Kontoen din er inaktiv. Kontakt administrator.';
            break;
        case 'user_not_linked':
            $error_message = 'Din super-user er ennå ikke koblet til firma. Kontakt oss for mer informasjon.';
            break;
        default:
            $error_message = 'En feil oppstod. Prøv igjen.';
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salesman Superportal - Innlogging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .news-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            max-height: 450px;
            overflow-y: auto;
        }
        .news-header {
            background: rgba(102, 126, 234, 0.15);
            color: rgba(102, 126, 234, 0.9);
            padding: 0.75rem;
            text-align: center;
            border-bottom: 1px solid rgba(102, 126, 234, 0.2);
        }
        .news-header h5 {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.8;
        }
        .news-item {
            border-bottom: 1px solid rgba(102, 126, 234, 0.1);
            padding: 0.75rem;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.3);
        }
        .news-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        .news-item:last-child {
            border-bottom: none;
        }
        .news-title {
            font-weight: 500;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .news-content {
            font-size: 0.75rem;
            color: rgba(0, 0, 0, 0.6);
            margin-bottom: 0.4rem;
            line-height: 1.3;
        }
        .news-meta {
            font-size: 0.7rem;
            color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header h2 {
            margin: 0;
            font-weight: 300;
        }
        .form-floating > .form-control:focus ~ label {
            color: #667eea;
        }
        .form-floating > .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-start mt-4">
            <!-- Login Form Column -->
            <div class="col-lg-5 col-md-6 mb-4">
                <div class="login-container">
                    <div class="login-header">
                        <img src="/attached_assets/26617BFC-F76D-481A-88CE-14CD3D5BBB66_1755491554819.png" alt="Salesman Logo" style="height: 100px; width: auto; margin-bottom: 15px; display: block; margin-left: auto; margin-right: auto; opacity: 0.95; background: rgba(255,255,255,0.8); border-radius: 50%; padding: 2px;">
                        <h2>Salesman Superportal</h2>
                        <p class="mb-0">Logg inn som superbruker</p>
                    </div>
                    
                    <div class="p-4">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle"></i>
                                <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="auth/company_auth.php">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Brukernavn" required autofocus>
                                <label for="username">Brukernavn</label>
                            </div>
                            
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Passord" required>
                                <label for="password">Passord</label>
                            </div>
                            
                            <button class="btn btn-primary btn-login w-100" type="submit">
                                <i class="bi bi-box-arrow-in-right"></i> Logg Inn
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- News Window Column -->
            <div class="col-lg-5 col-md-6 mb-4">
                <div class="news-container">
                    <div class="news-header">
                        <h5 class="mb-0">
                            <i class="bi bi-newspaper"></i> Siste nyheter
                        </h5>
                    </div>
                    <div id="news-content">
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Laster nyheter...</span>
                            </div>
                            <p class="mt-2 text-muted">Laster siste nyheter...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Load news when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadNews();
        });
        
        function loadNews() {
            fetch('api/get_latest_news.php')
                .then(response => response.json())
                .then(data => {
                    const newsContent = document.getElementById('news-content');
                    
                    if (data.success && data.news && data.news.length > 0) {
                        let newsHtml = '';
                        
                        data.news.forEach(item => {
                            newsHtml += `
                                <div class="news-item">
                                    <div class="news-title ${item.priority_class}">
                                        ${item.priority === 'critical' ? '<i class="bi bi-exclamation-triangle"></i> ' : ''}
                                        ${escapeHtml(item.title)}
                                    </div>
                                    <div class="news-content">
                                        ${escapeHtml(item.content)}
                                    </div>
                                    <div class="news-meta">
                                        <span><i class="bi bi-calendar"></i> ${item.formatted_date}</span>
                                        <span class="${item.priority_class}"><i class="bi bi-flag"></i> ${item.priority_text}</span>
                                    </div>
                                </div>
                            `;
                        });
                        
                        newsContent.innerHTML = newsHtml;
                    } else if (data.success && data.news && data.news.length === 0) {
                        newsContent.innerHTML = `
                            <div class="text-center p-4">
                                <div class="text-muted">
                                    <i class="bi bi-info-circle fs-3 mb-2 d-block"></i>
                                    <p>Ingen nyheter tilgjengelig</p>
                                </div>
                            </div>
                        `;
                    } else {
                        newsContent.innerHTML = `
                            <div class="text-center p-4">
                                <div class="text-muted">
                                    <i class="bi bi-exclamation-triangle fs-3 mb-2 d-block text-warning"></i>
                                    <p>Kunne ikke laste nyheter</p>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading news:', error);
                    document.getElementById('news-content').innerHTML = `
                        <div class="text-center p-4">
                            <div class="text-muted">
                                <i class="bi bi-exclamation-triangle fs-3 mb-2 d-block text-danger"></i>
                                <p>Feil ved lasting av nyheter</p>
                            </div>
                        </div>
                    `;
                });
        }
        
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>