<?php
require '../config/db.php';session_start();
if(!isset($_SESSION['user_id'])){header("Location: /cineclub/welcome.php");exit;}
$mid=intval($_POST['movie_id']??0);$uid=$_SESSION['user_id'];
$s=$pdo->prepare("SELECT id FROM votes WHERE user_id=? AND movie_id=?");$s->execute([$uid,$mid]);
if($s->fetch()){
    $pdo->prepare("DELETE FROM votes WHERE user_id=? AND movie_id=?")->execute([$uid,$mid]);
    $pdo->prepare("UPDATE movie_suggestions SET votes=GREATEST(votes-1,0) WHERE id=?")->execute([$mid]);
}else{
    $pdo->prepare("INSERT INTO votes(user_id,movie_id)VALUES(?,?)")->execute([$uid,$mid]);
    $pdo->prepare("UPDATE movie_suggestions SET votes=votes+1 WHERE id=?")->execute([$mid]);
}
header("Location: /cineclub/voting.php");exit;
?>
