<?php
require '../config/db.php';session_start();
if(!isset($_SESSION['user_id'])){header("Location: /cineclub/welcome.php");exit;}
$sid=intval($_POST['session_id']??0);$uid=$_SESSION['user_id'];
$st=in_array($_POST['status']??'',['attending','not_attending'])?$_POST['status']:'attending';
$pdo->prepare("INSERT INTO session_participants(session_id,user_id,status)VALUES(?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)")
    ->execute([$sid,$uid,$st]);
header("Location: /cineclub/participants.php");exit;
?>
