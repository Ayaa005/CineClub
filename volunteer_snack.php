<?php
require '../config/db.php';session_start();
if(!isset($_SESSION['user_id'])){header("Location: /cineclub/welcome.php");exit;}
$sid=intval($_POST['snack_id']??0);$uid=$_SESSION['user_id'];
$pdo->prepare("UPDATE snacks SET assigned_to=?,status='pending' WHERE id=? AND assigned_to IS NULL")
    ->execute([$uid,$sid]);
header("Location: /cineclub/snacks.php");exit;
?>
