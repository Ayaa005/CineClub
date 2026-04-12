<?php
// =============================================
// config.php — Connexion MySQL avec MySQLi
// =============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASSWORD', '');
define('DB_NAME',     'cineclub');   // nom exact de la base
define('DB_CHARSET',  'utf8mb4');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}
if (!$conn->set_charset(DB_CHARSET)) {
    die("Erreur charset");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

function isOrganizer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'organizer';
}
?>
