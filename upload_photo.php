<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /cineclub/welcome.php"); exit;
}

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $ftype   = mime_content_type($_FILES['photo']['tmp_name']);

    if (in_array($ftype, $allowed)) {
        $ext  = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $fn   = 'photo_' . uniqid() . '.' . $ext;
        $dest = '../uploads/gallery/' . $fn;

        // Créer le dossier si inexistant
        if (!is_dir('../uploads/gallery/')) {
            mkdir('../uploads/gallery/', 0755, true);
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $sess = $pdo->query("SELECT id FROM sessions WHERE status='past' ORDER BY session_date DESC LIMIT 1")->fetch();
            $cap  = htmlspecialchars(trim($_POST['caption'] ?? ''));
            $sid  = $sess ? $sess['id'] : null;
            $pdo->prepare("INSERT INTO gallery(session_id,image_path,caption,uploaded_by) VALUES(?,?,?,?)")
                ->execute([$sid, 'uploads/gallery/' . $fn, $cap, $_SESSION['user_id']]);
        }
    }
}

header("Location: /cineclub/gallery.php"); exit;
?>
