<?php
// =============================================
//  register.php — Inscription
//  Table users : id, username, email, password, role, created_at
// =============================================
require_once 'config.php';
redirectIfLoggedIn();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Validation username
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est obligatoire.";
    } elseif (strlen($username) > 100) {
        $errors[] = "Le nom d'utilisateur ne peut pas dépasser 100 caractères.";
    }

    // Validation email
    if (empty($email)) {
        $errors[] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    }

    // Validation password
    if (empty($password)) {
        $errors[] = "Le mot de passe est obligatoire.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une majuscule.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre.";
    }

    if ($password !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Vérifier si email déjà utilisé
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Cet email est déjà utilisé.";
        }
        $stmt->close();
    }

    // Insertion
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        // role par défaut : 'member' (défini dans la DB)
        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $username, $email, $hashed);

        if ($stmt->execute()) {
            $new_id = $conn->insert_id;

            $_SESSION['user_id']  = $new_id;
            $_SESSION['username'] = $username;
            $_SESSION['email']    = $email;
            $_SESSION['role']     = 'member';

            $stmt->close();
            header("Location: index.php?registered=1");
            exit();
        } else {
            $errors[] = "Erreur lors de l'inscription. Réessayez.";
            $stmt->close();
        }
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
    <title>CineClub - Register</title>
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
    <a href="login.php" class="login-btn">Login</a>
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
            <a href="login.php"    class="tab"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
            <a href="register.php" class="tab active"><i class="fa-solid fa-user-plus"></i> Register</a>
        </div>

        <div class="form-container">
            <h2>Join CineClub</h2>
            <p class="form-subtitle">Create your account to join the movie nights</p>

            <?php if (!empty($errors)): ?>
                <div class="form-msg error">
                    <?php foreach ($errors as $e): ?>
                        <div><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate>

                <div class="form-group">
                    <label><i class="fa-solid fa-user"></i> Username</label>
                    <input type="text" name="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Choose a username" required>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-envelope"></i> Email</label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="your@email.com" required>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-lock"></i> Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="regPassword"
                               placeholder="Create a password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('regPassword', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-lock"></i> Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" id="regConfirm"
                               placeholder="Confirm your password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('regConfirm', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="strength-bar-wrapper">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <p class="strength-text" id="strengthText"></p>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="agree" required>
                        I agree to the <a href="#">Terms &amp; Conditions</a>
                    </label>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fa-solid fa-user-plus"></i> Create Account
                </button>

            </form>

            <p class="switch-link">
                Already have an account? <a href="login.php">Sign in here</a>
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

document.getElementById('regPassword').addEventListener('input', function () {
    const val = this.value;
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let strength = 0;
    if (val.length >= 8)           strength++;
    if (/[A-Z]/.test(val))         strength++;
    if (/[0-9]/.test(val))         strength++;
    if (/[^A-Za-z0-9]/.test(val))  strength++;
    const levels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['', '#ff4444', '#ff8800', '#ffcc00', '#44cc44'];
    const widths = ['0%', '25%', '50%', '75%', '100%'];
    bar.style.width      = widths[strength];
    bar.style.background = colors[strength];
    text.textContent     = strength > 0 ? 'Password strength: ' + levels[strength] : '';
    text.style.color     = colors[strength];
});
</script>
</body>
</html>
