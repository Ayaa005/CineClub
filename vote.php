<?php
session_start();
include("config.php");

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if(isset($_POST['film_id'])){
    $film_id = $_POST['film_id'];

    $check = $conn->query("SELECT * FROM votes WHERE user_id=$user_id AND movie_id=$film_id");

    if($check->num_rows == 0){
        $conn->query("INSERT INTO votes (user_id, movie_id) VALUES ($user_id, $film_id)");
    }

    header("Location: voting.php");
}
?>