<?php
require '../config/db.php';
session_start();
if($_SERVER['REQUEST_METHOD']!=='POST'){header("Location: /cineclub/welcome.php");exit;}
$username=htmlspecialchars(trim($_POST['username']??''));
$email=htmlspecialchars(trim($_POST['email']??''));
$password=$_POST['password']??'';
$role=$_POST['role']??'organizer';
$code=strtoupper(trim($_POST['invite_code']??''));
if(empty($username)||empty($email)||empty($password)){header("Location: /cineclub/welcome.php?error=empty&tab=register");exit;}
if(strlen($password)<6){header("Location: /cineclub/welcome.php?error=short_password&tab=register");exit;}
if($role==='organizer'){
    $s=$pdo->prepare("SELECT id FROM users WHERE role='organizer' LIMIT 1");$s->execute();
    if($s->fetch()){header("Location: /cineclub/welcome.php?error=organizer_exists&tab=register");exit;}
}
$invite=null;
if($role==='member'){
    if(empty($code)){header("Location: /cineclub/welcome.php?error=invalid_code&tab=register");exit;}
    $s=$pdo->prepare("SELECT * FROM invitations WHERE code=? AND used_by IS NULL AND expires_at>NOW()");
    $s->execute([$code]);$invite=$s->fetch();
    if(!$invite){header("Location: /cineclub/welcome.php?error=invalid_code&tab=register");exit;}
}
$hash=password_hash($password,PASSWORD_DEFAULT);
try{
    $s=$pdo->prepare("INSERT INTO users(username,email,password,role)VALUES(?,?,?,?)");
    $s->execute([$username,$email,$hash,$role]);
    $uid=$pdo->lastInsertId();
    if($invite){
        $pdo->prepare("UPDATE invitations SET used_by=?,used_at=NOW() WHERE id=?")->execute([$uid,$invite['id']]);
        if(!empty($invite['session_id'])){
            $pdo->prepare("INSERT IGNORE INTO session_participants(session_id,user_id,status)VALUES(?,?,'attending')")->execute([$invite['session_id'],$uid]);
        }
    }
    if($role==='organizer'){
        $sess=$pdo->query("SELECT id FROM sessions WHERE status='upcoming' ORDER BY session_date ASC LIMIT 1")->fetch();
        if($sess) $pdo->prepare("INSERT IGNORE INTO session_participants(session_id,user_id,status)VALUES(?,?,'attending')")->execute([$sess['id'],$uid]);
    }
    session_regenerate_id(true);
    $_SESSION['user_id']=$uid;$_SESSION['username']=$username;$_SESSION['role']=$role;
    header("Location: /cineclub/index.php");
}catch(PDOException $e){
    header("Location: /cineclub/welcome.php?error=email_taken&tab=register");
}
exit;
?>
