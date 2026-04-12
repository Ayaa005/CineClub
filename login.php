<?php
// =============================================
//  login.php — Connexion
//  Table users : id, username, email, password, role, created_at
// =============================================
require_once 'config.php';
redirectIfLoggedIn();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email)) {
        $errors[] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    }

    if (empty($password)) {
        $errors[] = "Le mot de passe est obligatoire.";
    }

    if (empty($errors)) {
        // Colonnes réelles : id, username, email, password, role
        $stmt = $conn->prepare(
            "SELECT id, username, email, password, role FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['role']     = $user['role'];

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                }

                $stmt->close();
                header("Location: index.php?login=success");
                exit();
            } else {
                $errors[] = "Email ou mot de passe incorrect.";
            }
        } else {
            $errors[] = "Email ou mot de passe incorrect.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>CineClub - Login</title>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <img src="Screenshot_2026-02-12_203643-removebg-preview.png" alt="CineClub Logo">
    </div>
    <ul class="nav-links">
        <li><a href="index.php"><i class="fa-solid fa-house"></i> Home</a></li>
        <li><a href="planning.php"><i class="fa-solid fa-calendar"></i> Planning</a></li>
        <li><a href="voting.php"><i class="fa-solid fa-check-to-slot"></i> Voting</a></li>
        <li><a href="participants.php"><i class="fa-solid fa-users"></i> Participants</a></li>
        <li><a href="snaks.php"><i class="fa-solid fa-cookie-bite"></i> Snacks</a></li>
        <li><a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a></li>
        <li><a href="gallery.php"><i class="fa-solid fa-image"></i> Gallery</a></li>
    </ul>
    <a href="login.php" class="login-btn active-btn">Login</a>
</nav>

<div class="auth-wrapper">

    <div class="auth-left">
        <div class="auth-left-content">
            <div class="brand">
                <i class="fa-solid fa-film"></i>
                <h2>CINE<span>CLUB</span></h2>
            </div>
            <p class="tagline">Your movie nights,<br>perfectly organized.</p>
            <ul class="feature-list">
                <li><i class="fa-solid fa-check-to-slot"></i> Vote for the next film</li>
                <li><i class="fa-solid fa-calendar"></i> View upcoming sessions</li>
                <li><i class="fa-solid fa-cookie-bite"></i> Organize snacks</li>
                <li><i class="fa-solid fa-image"></i> Share memories in gallery</li>
            </ul>
        </div>
    </div>

    <div class="auth-right">
        <div class="tabs">
            <a href="login.php"    class="tab active"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
            <a href="register.php" class="tab"><i class="fa-solid fa-user-plus"></i> Register</a>
        </div>

        <div class="form-container">
            <h2>Welcome Back</h2>
            <p class="form-subtitle">Sign in to your CineClub account</p>

            <?php if (isset($_GET['registered'])): ?>
                <div class="form-msg success">
                    <i class="fa-solid fa-circle-check"></i> Account created! You are now logged in.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
                <div class="form-msg success">
                    <i class="fa-solid fa-circle-check"></i> You have been logged out.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="form-msg error">
                    <?php foreach ($errors as $e): ?>
                        <div><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>

                <div class="form-group">
                    <label><i class="fa-solid fa-envelope"></i> Email</label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="your@email.com" required>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-lock"></i> Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="loginPassword"
                               placeholder="Enter your password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('loginPassword', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In
                </button>

            </form>

            <p class="switch-link">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>
