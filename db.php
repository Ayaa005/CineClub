<?php
$conn = new mysqli("localhost", "root", "", "cineclub");

if ($conn->connect_error) {
    die("Erreur connexion: " . $conn->connect_error);
}