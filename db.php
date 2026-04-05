<?php
$host   = 'localhost';
$dbname = 'cineclub';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h2 style='color:red;font-family:sans-serif;padding:40px'>
        Erreur BDD : " . $e->getMessage() . "
        <br><small>Vérifie que MySQL est démarré dans XAMPP</small>
    </h2>");
}
?>