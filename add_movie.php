<?php
session_start();
include("config.php");

$user_id = $_SESSION['user_id'];

if(isset($_POST['titre'])){
    $titre = $_POST['titre'];
    $genre = $_POST['genre'];
    $annee = $_POST['annee'];
    $image = $_POST['image'];
    $description = $_POST['description'];

    $sql = "INSERT INTO movies (title, genre, annee, poster_url, description, user_id)<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /cineclub/welcome.php"); exit;
}

$title  = htmlspecialchars(trim($_POST['title']  ?? ''));
$year   = intval($_POST['year'] ?? 0);
$poster = trim($_POST['poster'] ?? '');
$uid    = $_SESSION['user_id'];

if (empty($title)) {
    header("Location: /cineclub/voting.php"); exit;
}

// Si l'affiche vient de TMDB (URL externe) → télécharger en local
if (!empty($poster) && str_starts_with($poster, 'tmdb:')) {
    $url = substr($poster, 5);

    // Créer le dossier si inexistant
    if (!is_dir('../uploads/posters/')) {
        mkdir('../uploads/posters/', 0755, true);
    }

    $fn   = 'poster_' . uniqid() . '.jpg';
    $dest = '../uploads/posters/' . $fn;

    // Télécharger avec contexte pour éviter les blocages
    $opts = [
        'http' => [
            'method'  => 'GET',
            'timeout' => 10,
            'header'  => "User-Agent: Mozilla/5.0\r\n"
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]
    ];
    $ctx  = stream_context_create($opts);
    $data = @file_get_contents($url, false, $ctx);

    if ($data !== false && strlen($data) > 1000) {
        file_put_contents($dest, $data);
        $poster = 'uploads/posters/' . $fn;
    } else {
        $poster = 'uploads/posters/default.jpg';
    }
} elseif (empty($poster)) {
    $poster = 'uploads/posters/default.jpg';
}

$pdo->prepare("INSERT INTO movie_suggestions(title,year,poster,suggested_by) VALUES(?,?,?,?)")
    ->execute([$title, $year ?: null, $poster, $uid]);

header("Location: /cineclub/voting.php"); exit;
?>

        VALUES ('$titre', '$genre', '$annee', '$image', '$description', $user_id)";

    if($conn->query($sql)){
        header("Location: voting.php");
        exit();
    } else {
        echo "Erreur : " . $conn->error;
    }
}
?>
