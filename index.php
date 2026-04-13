<?php
require 'config/db.php';
session_start();

// Prochaine session à venir (avec photo de couverture)
$next = $pdo->query("
    SELECT s.*
    FROM sessions s
    WHERE s.status='upcoming'
    AND s.session_date >= CURDATE()
    ORDER BY s.session_date ASC
    LIMIT 1
")->fetch();

// Compter les participants de la prochaine session
$next_count = 0;
if ($next) {
    $sc = $pdo->prepare("SELECT COUNT(*) FROM session_participants WHERE session_id=? AND status='attending'");
    $sc->execute([$next['id']]);
    $next_count = $sc->fetchColumn();
}

// Top films votés
$top = $pdo->query("
    SELECT * FROM movie_suggestions
    ORDER BY votes DESC
    LIMIT 4
")->fetchAll();

// Sessions récentes (passées)
$recent = $pdo->query("
    SELECT s.*,
           AVG(r.rating) AS avg_r,
           COUNT(DISTINCT sp.id) AS np
    FROM sessions s
    LEFT JOIN session_ratings r ON r.session_id=s.id
    LEFT JOIN session_participants sp ON sp.session_id=s.id
    WHERE s.status='past'
    GROUP BY s.id
    ORDER BY s.session_date DESC
    LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>CineClub</title>
    <link rel="stylesheet" href="/cineclub/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-content">
        <h1><span style="color:#fff">CINE</span><span style="color:var(--red)">CLUB</span></h1>
        <p>Organize unforgettable movie nights with your friends.<br>Vote, plan, snack, enjoy.</p>
        <div class="hero-btns">
            <a href="/cineclub/voting.php" class="btn-red lg">▶ Start Voting</a>
            <a href="/cineclub/planning.php" class="btn-dark lg">📅 View Schedule</a>
        </div>
    </div>
</section>

<!-- NEXT SESSION -->
<?php if ($next): ?>
<div class="container">
    <div class="section-head">
        <h2 class="section-title">NEXT <span>SESSION</span></h2>
        <a href="/cineclub/planning.php" class="see-all">View planning →</a>
    </div>
    <div class="card next-card">
        <img src="/cineclub/<?= htmlspecialchars($next['movie_poster']) ?>"
             onerror="this.src='/cineclub/uploads/posters/default.jpg'"
             alt="<?= htmlspecialchars($next['movie_title']) ?>">
        <div class="next-info">
            <h3><?= htmlspecialchars($next['movie_title']) ?></h3>
            <p>
                <svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:var(--red);flex-shrink:0"><path d="M19 3h-1V1h-2v2H8V1H6v2H5C3.89 3 3 3.9 3 5v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                <?= date('l, F j', strtotime($next['session_date'])) ?>
            </p>
            <p>
                <svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:var(--red);flex-shrink:0"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm.5 5v5.3l4.5 2.7-.7 1.2L11 13V7z"/></svg>
                <?= substr($next['session_time'], 0, 5) ?>
            </p>
            <?php if (!empty($next['location'])): ?>
            <p>
                <svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:var(--red);flex-shrink:0"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                <?= htmlspecialchars($next['location']) ?>
            </p>
            <?php endif; ?>
            <p>
                <svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:var(--red);flex-shrink:0"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                <?= $next_count ?> attending
            </p>
            <a href="/cineclub/planning.php" class="btn-red" style="margin-top:14px">
                View details →
            </a>
        </div>
    </div>
</div>
<?php else: ?>
<div class="container">
    <div class="section-head">
        <h2 class="section-title">NEXT <span>SESSION</span></h2>
    </div>
    <div class="empty-state" style="padding:40px">
        <div class="empty-icon">🎬</div>
        <h3>No upcoming session</h3>
        <p>L'organisateur n'a pas encore planifié de soirée.</p>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'organizer'): ?>
        <a href="/cineclub/planning.php" class="btn-red" style="margin-top:14px">
            + Add Session
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- TOP VOTED -->
<?php if (!empty($top)): ?>
<div class="container">
    <div class="section-head">
        <h2 class="section-title">TOP <span>VOTED</span></h2>
        <a href="/cineclub/voting.php" class="see-all">See all →</a>
    </div>
    <div class="movies-grid4">
        <?php foreach ($top as $m): ?>
        <div class="mcard">
            <img src="/cineclub/<?= htmlspecialchars($m['poster']) ?>"
                 onerror="this.src='/cineclub/uploads/posters/default.jpg'"
                 alt="<?= htmlspecialchars($m['title']) ?>">
            <div class="mcard-badge">⭐ <?= $m['votes'] ?></div>
            <div class="mcard-hover">
                <strong><?= htmlspecialchars($m['title']) ?></strong>
                <span><?= $m['year'] ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- RECENT SESSIONS -->
<?php if (!empty($recent)): ?>
<div class="container">
    <div class="section-head">
        <h2 class="section-title">RECENT <span>SESSIONS</span></h2>
        <a href="/cineclub/archives.php" class="see-all">View archives →</a>
    </div>
    <div class="recent-list">
        <?php foreach ($recent as $r): ?>
        <div class="recent-item">
            <img src="/cineclub/<?= htmlspecialchars($r['movie_poster']) ?>"
                 onerror="this.src='/cineclub/uploads/posters/default.jpg'"
                 alt="">
            <div class="recent-info">
                <strong><?= htmlspecialchars($r['movie_title']) ?></strong>
                <span>
                    <?= date('d/m/Y', strtotime($r['session_date'])) ?>
                    <?= !empty($r['location']) ? '· 📍 ' . htmlspecialchars($r['location']) : '' ?>
                    · <?= $r['np'] ?> people
                </span>
            </div>
            <?php if ($r['avg_r']): ?>
            <span class="recent-rating">⭐ <?= number_format($r['avg_r'],1) ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

</body>
</html>
