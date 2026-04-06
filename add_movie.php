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

    $sql = "INSERT INTO movies (title, genre, annee, poster_url, description, user_id)
        VALUES ('$titre', '$genre', '$annee', '$image', '$description', $user_id)";

    if($conn->query($sql)){
        header("Location: voting.php");
        exit();
    } else {
        echo "Erreur : " . $conn->error;
    }
}
?>