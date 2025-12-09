<?php
/**
 * Script de réinitialisation du mot de passe administrateur
 *
 * ATTENTION : Ce script doit être SUPPRIMÉ après utilisation pour des raisons de sécurité !
 *
 * Usage : php reset_admin_password.php
 */

// Charger l'autoloader de Composer
require __DIR__ . '/vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Paramètres de connexion à la base de données
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'geofencing';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

// Nouvelles informations admin
$adminEmail = 'iues@insamD.com';
$newPassword = 'admin1234';

echo "\n";
echo "================================================\n";
echo "  RÉINITIALISATION MOT DE PASSE ADMINISTRATEUR\n";
echo "================================================\n";
echo "\n";

try {
    // Connexion à la base de données
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✓ Connexion à la base de données réussie\n";
    echo "  Base de données: {$database}\n";
    echo "  Serveur: {$host}:{$port}\n\n";

    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $user = $stmt->fetch();

    // Hash du nouveau mot de passe (bcrypt - compatible Laravel)
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    if (!$user) {
        echo "⚠ Utilisateur non trouvé. Création d'un nouvel administrateur...\n\n";

        // Récupérer l'ID du rôle Admin
        $stmt = $pdo->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1");
        $adminRole = $stmt->fetch();

        if (!$adminRole) {
            echo "✗ ERREUR : Le rôle 'admin' n'existe pas dans la base de données\n";
            exit(1);
        }

        // Créer le nouvel utilisateur administrateur
        $stmt = $pdo->prepare("
            INSERT INTO users (
                role_id, email, password, first_name, last_name,
                employee_type, is_active, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $adminRole['id'],
            $adminEmail,
            $hashedPassword,
            'Administrateur',
            'IUES-INSAM',
            'direction',
            1
        ]);

        $userId = $pdo->lastInsertId();

        echo "✓ Nouvel utilisateur administrateur créé :\n";
        echo "  ID: {$userId}\n";
        echo "  Email: {$adminEmail}\n";
        echo "  Nom: Administrateur IUES-INSAM\n";
        echo "  Rôle: Admin\n\n";

    } else {
        echo "✓ Utilisateur trouvé :\n";
        echo "  ID: {$user['id']}\n";
        echo "  Email: {$user['email']}\n";
        echo "  Nom: {$user['first_name']} {$user['last_name']}\n\n";

        // Mise à jour du mot de passe
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
        $stmt->execute([$hashedPassword, $adminEmail]);

        echo "✓ Mot de passe mis à jour avec succès !\n\n";
    }

    echo "================================================\n";
    echo "  NOUVELLES INFORMATIONS DE CONNEXION\n";
    echo "================================================\n";
    echo "  Email    : {$adminEmail}\n";
    echo "  Mot de passe : {$newPassword}\n";
    echo "================================================\n\n";

    echo "⚠️  IMPORTANT : SUPPRIMEZ CE SCRIPT IMMÉDIATEMENT !\n";
    echo "    Commande : rm -f " . __FILE__ . "\n\n";

} catch (PDOException $e) {
    echo "\n✗ ERREUR DE CONNEXION À LA BASE DE DONNÉES :\n";
    echo "  Message: {$e->getMessage()}\n\n";
    echo "Vérifiez vos paramètres dans le fichier .env :\n";
    echo "  DB_HOST={$host}\n";
    echo "  DB_PORT={$port}\n";
    echo "  DB_DATABASE={$database}\n";
    echo "  DB_USERNAME={$username}\n";
    echo "  DB_PASSWORD=" . (empty($password) ? '(vide)' : '(défini)') . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n✗ ERREUR INATTENDUE :\n";
    echo "  {$e->getMessage()}\n\n";
    exit(1);
}
