<?php
// Use environment variables if available (Vercel), otherwise fallback to localhost (Local XAMPP/WAMP)
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "kisandirect";
$port = getenv('DB_PORT') ?: 3306; 

try {
    // Added port to the connection string
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    
    // Set error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // On Vercel, showing the full error helps debug, but in production, be careful.
    die("DB Connection failed: " . $e->getMessage());
}
?>