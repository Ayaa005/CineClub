<?php
$host   = 'localhost';
$dbname = 'cineclub';
$user   = 'root';
$pass   = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,        PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:40px;color:#ff6b6b;background:#141414;min-height:100vh'>
        <h2>Erreur BDD</h2><p>".$e->getMessage()."</p>
        <p style='color:#888'>Vérifie que MySQL est démarré dans XAMPP</p>
    </div>");
}
?>
