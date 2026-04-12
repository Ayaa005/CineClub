<?php
// =============================================
//  logout.php — Déconnexion
// =============================================
require_once 'config.php';

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

session_destroy();

header("Location: login.php?logout=1");
exit();
?>
