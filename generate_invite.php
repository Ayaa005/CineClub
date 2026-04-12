<?php
require '../config/db.php';session_start();
if(!isset($_SESSION['role'])||$_SESSION['role']!=='organizer'){header("Location: /cineclub/welcome.php");exit;}
function gc(){$c='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';$r='';for($i=0;$i<8;$i++)$r.=$c[random_int(0,strlen($c)-1)];return $r;}
$code=gc();$uid=$_SESSION['user_id'];$exp=date('Y-m-d H:i:s',strtotime('+24 hours'));
$sess=$pdo->query("SELECT id FROM sessions WHERE status='upcoming' ORDER BY session_date ASC LIMIT 1")->fetch();
$sid=$sess?$sess['id']:null;
$pdo->prepare("UPDATE invitations SET expires_at=NOW() WHERE created_by=? AND used_by IS NULL")->execute([$uid]);
$pdo->prepare("INSERT INTO invitations(code,created_by,session_id,expires_at)VALUES(?,?,?,?)")->execute([$code,$uid,$sid,$exp]);
header("Location: /cineclub/participants.php");exit;
?>
