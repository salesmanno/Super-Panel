<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Sjekk innlogging
if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    header("Location: /pages/login.php");
    exit;
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $newPassword = $_POST['new_password'];
    
    if (strlen($newPassword) < 6) {
        $message = 'Passordet må være minst 6 tegn';
    } else {
        try {
            $db = getDBConnection();
            $db->query("SET collation_connection='utf8mb4_general_ci'");
            
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $stmt = $db->prepare("UPDATE tenant_companies SET admin_password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['company_id']]);
            
            $message = "Passord oppdatert! Du kan nå bruke passordet '$newPassword' for å låse opp Firmadetaljer.";
            $success = true;
        } catch (Exception $e) {
            $message = "Feil: " . $e->getMessage();
        }
    }
}

// Sjekk om passord allerede eksisterer
$hasPassword = false;
try {
    $db = getDBConnection();
    $db->query("SET collation_connection='utf8mb4_general_ci'");
    
    $stmt = $db->prepare("SELECT admin_password_hash FROM tenant_companies WHERE id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    $company = $stmt->fetch();
    
    $hasPassword = !empty($company['admin_password_hash']);
} catch (Exception $e) {
    $message = "Database-tilkobling feilet: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sett Firma Administratorpassord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-key me-2"></i>Administratorpassord</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($hasPassword): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-check-circle me-2"></i>
                                Administratorpassord er allerede satt for dette firmaet.
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-muted mb-4">
                            Dette passordet brukes for å låse opp tilgang til firmadetaljer (Firmadata, Om oss, Stillinger) i dashboardet.
                        </p>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">
                                    <?php echo $hasPassword ? 'Endre' : 'Nytt'; ?> Passord
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Skriv inn nytt passord (min. 6 tegn)"
                                       required>
                                <small class="form-text text-muted">Minimum 6 tegn</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save me-2"></i>Lagre Passord
                                </button>
                                <a href="/pages/company_dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Tilbake til Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
