<?php
require 'config/db.php';session_start();
$sessions=$pdo->query("SELECT s.*,AVG(r.rating) AS avg_r,COUNT(DISTINCT sp.id) AS np,GROUP_CONCAT(DISTINCT r.comment SEPARATOR ' | ') AS comments FROM sessions s LEFT JOIN session_ratings r ON r.session_id=s.id LEFT JOIN session_participants sp ON sp.session_id=s.id AND sp.status='attending' WHERE s.status='past' GROUP BY s.id ORDER BY s.session_date DESC")->fetchAll();
$total=count($sessions);
$ratings=array_filter(array_column($sessions,'avg_r'));
$avg=!empty($ratings)?round(array_sum($ratings)/count($ratings),1):0;
?>
<!DOCTYPE html><html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Archives - CineClub</title>
<link rel="stylesheet" href="/cineclub/css/style.css">
</head><body>
<?php include 'includes/navbar.php';?>
<div class="page-body"><div class="container">
<div class="page-header"><div><h1 class="page-title">ARCH<span>IVES</span></h1><p class="subtitle">Relive past movie nights</p></div></div>
<div class="stat-row">
    <span class="stat-chip stat-red">🎬 <?=$total?> movie nights</span>
    <span class="stat-chip stat-gray">⭐ Avg: <?=$avg?></span>
</div>
<?php if(empty($sessions)):?>
<div class="empty-state"><div class="empty-icon">🗂</div><h3>No archives yet</h3><p>Les soirées passées apparaîtront ici.</p></div>
<?php else:?>
<div class="arch-grid">
<?php foreach($sessions as $s):?>
<div class="arch-card">
    <?php if($s['avg_r']):?>
    <div class="arch-badge">⭐ <?=number_format($s['avg_r'],1)?></div>
    <?php endif;?>
    <img src="/cineclub/<?=htmlspecialchars($s['movie_poster'])?>" onerror="this.src='/cineclub/uploads/posters/default.jpg'" alt="<?=htmlspecialchars($s['movie_title'])?>">
    <div class="arch-info">
        <h3><?=htmlspecialchars($s['movie_title'])?></h3>
        <p>📅 <?=date('d/m/Y',strtotime($s['session_date']))?> · 👥 <?=$s['np']?> people</p>
        <?php if($s['comments']):?><p style="font-style:italic;color:var(--text2)">"<?=htmlspecialchars(mb_strimwidth($s['comments'],0,70,'...'))?>"</p><?php endif;?>
    </div>
</div>
<?php endforeach;?>
</div>
<?php endif;?>
</div></div>
</body></html>
