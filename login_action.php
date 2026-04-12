<?php
require '../config/db.php';
session_start();
if ($_SERVER['REQUEST_METHOD']!=='POST'){header("Location: /cineclub/welcome.php");exit;}
$email=$_POST['email']??''; $password=$_POST['password']??''; $role=$_POST['login_type']??'organizer';
if(empty($email)||empty($password)){header("Location: /cineclub/welcome.php?error=empty");exit;}
$s=$pdo->prepare("SELECT * FROM users WHERE email=?"); $s->execute([$email]); $u=$s->fetch();
if(!$u){header("Location: /cineclub/welcome.php?error=".($role==='organizer'?'not_organizer':'not_member'));exit;}
if(!password_verify($password,$u['password'])){header("Location: /cineclub/welcome.php?error=wrong");exit;}
if($u['role']!==$role){header("Location: /cineclub/welcome.php?error=wrong_role&tab=login");exit;}
session_regenerate_id(true);
$_SESSION['user_id']=$u['id'];$_SESSION['username']=$u['username'];$_SESSION['role']=$u['role'];
header("Location: /cineclub/index.php");exit;
?>
