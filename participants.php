<?php
require 'config/db.php';session_start();
$sess=$pdo->query("SELECT * FROM sessions WHERE status='upcoming' ORDER BY session_date ASC LIMIT 1")->fetch();
$ptcs=[];$ac=0;$nc=0;$my=null;
if($sess){
    $s=$pdo->prepare("SELECT u.id,u.username,u.role,sp.status FROM session_participants sp JOIN users u ON u.id=sp.user_id WHERE sp.session_id=? ORDER BY u.role DESC,u.username ASC");
    $s->execute([$sess['id']]);$ptcs=$s->fetchAll();
    foreach($ptcs as $p){if($p['status']==='attending')$ac++;else $nc++;}
    if(isset($_SESSION['user_id'])){
        $s2=$pdo->prepare("SELECT status FROM session_participants WHERE session_id=? AND user_id=?");
        $s2->execute([$sess['id'],$_SESSION['user_id']]);
        $row=$s2->fetch();$my=$row?$row['status']:null;
    }
}
$code=null;
if(isset($_SESSION['role'])&&$_SESSION['role']==='organizer'){
    $s3=$pdo->prepare("SELECT code FROM invitations WHERE created_by=? AND used_by IS NULL AND expires_at>NOW() ORDER BY created_at DESC LIMIT 1");
    $s3->execute([$_SESSION['user_id']]);$code=$s3->fetchColumn();
}
?>
<!DOCTYPE html><html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Participants - CineClub</title>
<link rel="stylesheet" href="/cineclub/css/style.css">
</head><body>
<?php include 'includes/navbar.php';?>
<div class="page-body"><div class="container">
<div class="page-header">
    <div><h1 class="page-title">PARTI<span>CIPANTS</span></h1>
    <p class="subtitle"><?=$sess?'Prochaine soirée : <strong>'.htmlspecialchars($sess['movie_title']).'</strong> — '.date('d/m/Y',strtotime($sess['session_date'])):'See who\'s coming to the next movie night'?></p>
    </div>
    <?php if(isset($_SESSION['role'])&&$_SESSION['role']==='organizer'&&$sess):?>
    <button class="btn-red" onclick="document.getElementById('m-invite').style.display='flex'">+ Invite Members</button>
    <?php endif;?>
</div>

<?php if(!$sess&&isset($_SESSION['role'])&&$_SESSION['role']==='organizer'):?>
<div class="empty-state">
    <div class="empty-icon">🎬</div><h3>No session yet</h3>
    <p>Crée une session pour commencer !</p>
    <button class="btn-red" style="margin-top:14px" onclick="document.getElementById('m-create').style.display='flex'">+ Create Session</button>
</div>
<?php elseif(!$sess):?>
<div class="empty-state"><div class="empty-icon">🎬</div><h3>No session yet</h3><p>L'organisateur n'a pas encore créé de session.</p></div>
<?php elseif(empty($ptcs)):?>
<div class="empty-state"><div class="empty-icon">👥</div><h3>No participants yet</h3>
<?php if(isset($_SESSION['role'])&&$_SESSION['role']==='organizer'):?>
<p>Génère un code d'invitation et partage-le !</p>
<button class="btn-red" style="margin-top:14px" onclick="document.getElementById('m-invite').style.display='flex'">🔗 Invite Members</button>
<?php else:?><p>L'organisateur n'a pas encore invité de membres.</p><?php endif;?>
</div>
<?php else:?>
<div class="status-row">
    <span class="badge badge-green">✓ <?=$ac?> attending</span>
    <span class="badge badge-gray">✗ <?=$nc?> can't make it</span>
</div>
<div class="ptc-grid">
<?php foreach($ptcs as $p):?>
<div class="card ptc-card <?=$p['status']==='not_attending'?'absent':''?>">
    <div class="ptc-av <?=$p['status']==='not_attending'?'gray':'red'?>">
        <?=strtoupper(mb_substr($p['username'],0,1))?>
    </div>
    <div class="ptc-info">
        <strong><?=htmlspecialchars($p['username'])?><?=$p['role']==='organizer'?' 👑':''?></strong>
        <span><?=$p['role']==='organizer'?'Organizer':'Member'?></span>
    </div>
    <span class="ptc-check <?=$p['status']==='attending'?'ok':'no'?>"><?=$p['status']==='attending'?'✓':'✗'?></span>
</div>
<?php endforeach;?>
</div>
<?php if(isset($_SESSION['user_id'])):?>
<div class="card rsvp-box">
    <h3>Your RSVP :</h3>
    <div class="btns">
        <form method="POST" action="/cineclub/actions/update_attendance.php">
            <input type="hidden" name="session_id" value="<?=$sess['id']?>">
            <button name="status" value="attending" class="btn-red <?=$my==='attending'?'btn-active':''?>">✓ I'm attending</button>
        </form>
        <form method="POST" action="/cineclub/actions/update_attendance.php">
            <input type="hidden" name="session_id" value="<?=$sess['id']?>">
            <button name="status" value="not_attending" class="btn-dark <?=$my==='not_attending'?'btn-active':''?>">✗ Can't make it</button>
        </form>
    </div>
</div>
<?php else:?>
<p style="color:var(--text3);margin-top:18px;font-size:13px;text-align:center"><a href="/cineclub/welcome.php" style="color:var(--red)">Connecte-toi</a> pour indiquer ta présence.</p>
<?php endif;?>
<?php endif;?>
</div></div>

<?php if(isset($_SESSION['role'])&&$_SESSION['role']==='organizer'):?>
<!-- Modal Invite -->
<div id="m-invite" class="modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
<div class="modal">
    <h2>🔗 Invite Members</h2>
    <p style="font-size:13px;color:var(--text2);margin-bottom:14px">Génère un code unique, partage-le par WhatsApp ou SMS. Le code expire dans 24h.</p>
    <?php if($code):?>
    <div class="code-box"><span class="code-val"><?=$code?></span>
        <button class="btn-copy" onclick="navigator.clipboard.writeText('<?=$code?>').then(()=>{this.textContent='✓ Copied!';this.style.color='var(--green)';setTimeout(()=>{this.textContent='📋 Copy';this.style.color=''},2000)})">📋 Copy</button>
    </div>
    <p class="code-hint">Tes amis utilisent ce code lors de leur inscription.</p>
    <form method="POST" action="/cineclub/actions/generate_invite.php">
        <button type="submit" class="btn-dark" style="width:100%;justify-content:center;margin-top:12px">🔄 New Code</button>
    </form>
    <?php else:?>
    <div class="no-code">Aucun code actif pour le moment.</div>
    <form method="POST" action="/cineclub/actions/generate_invite.php">
        <button type="submit" class="btn-red" style="width:100%;justify-content:center;margin-top:12px">🔗 Generate Code</button>
    </form>
    <?php endif;?>
    <button class="btn-dark" style="width:100%;justify-content:center;margin-top:8px" onclick="document.getElementById('m-invite').style.display='none'">Close</button>
</div>
</div>

<!-- Modal Create Session -->
<div id="m-create" class="modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
<div class="modal">
    <h2>🎬 Create Session</h2>
    <form method="POST" action="/cineclub/actions/create_session.php">
        <div class="form-field"><label>Movie Title *</label><input type="text" name="movie_title" required></div>
        <div class="form-field"><label>Date *</label><input type="date" name="session_date" min="<?=date('Y-m-d')?>" required></div>
        <div class="form-field"><label>Time</label><input type="time" name="session_time" value="20:00"></div>
        <div class="modal-btns">
            <button type="submit" class="btn-red">Create</button>
            <button type="button" class="btn-dark" onclick="document.getElementById('m-create').style.display='none'">Cancel</button>
        </div>
    </form>
</div>
</div>
<?php endif;?>
</body></html>
