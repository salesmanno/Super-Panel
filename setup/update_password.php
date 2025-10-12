<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Konfigurer her
$username = 'atlek';
$newPassword = 'bergsoy123';

echo "Password Update Script\n";
echo "=====================\n\n";

try {
    $conn = getDBConnection();
    
    if (!$conn) {
        die("❌ Kunne ikke koble til database\n");
    }
    
    // Generer riktig password hash
    $passwordHash = hashPassword($newPassword);
    
    echo "Username: $username\n";
    echo "Nytt passord: $newPassword\n";
    echo "Password hash: $passwordHash\n\n";
    
    // Sjekk om brukeren eksisterer
    $stmt = $conn->prepare("SELECT id, username, email, name FROM tenant_admins WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die("❌ Bruker '$username' finnes ikke i databasen\n");
    }
    
    echo "Fant bruker:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Username: {$user['username']}\n";
    echo "  Email: {$user['email']}\n";
    echo "  Name: {$user['name']}\n\n";
    
    // Oppdater password
    $stmt = $conn->prepare("UPDATE tenant_admins SET password_hash = ? WHERE username = ?");
    $stmt->execute([$passwordHash, $username]);
    
    echo "✅ Passord oppdatert!\n\n";
    echo "Du kan nå logge inn med:\n";
    echo "  Brukernavn: $username\n";
    echo "  Passord: $newPassword\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
