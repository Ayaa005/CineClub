<?php
require 'config/db.php';
session_start();
$errors=[];$success='';

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_SESSION['role']) && $_SESSION['role']==='organizer'){
    if(isset($_POST['action']) && $_POST['action']==='add'){
        $movie_id = intval($_POST['movie_id'] ?? 0);
        $d = $_POST['session_date'] ?? '';
        $h = $_POST['session_time'] ?? '20:00';
        $loc = htmlspecialchars(trim($_POST['location'] ?? 'TBD'));

        if(!$movie_id) $errors[]='Sélectionne un film.';
        if(empty($d))  $errors[]='La date est obligatoire.';
        elseif(strtotime($d)<strtotime('today')) $errors[]='La date doit être dans le futur.';

        if(empty($errors)){
            // Récupérer le titre et l'affiche du film sélectionné
            $sm = $pdo->prepare("SELECT title, poster FROM movie_suggestions WHERE id=?");
            $sm->execute([$movie_id]);
            $movie = $sm->fetch();

            if($movie){
                $pdo->prepare("INSERT INTO sessions(movie_title,movie_poster,session_date,session_time,location,status,created_by) VALUES(?,?,?,?,?,'upcoming',?)")
                    ->execute([$movie['title'], $movie['poster'], $d, $h, $loc, $_SESSION['user_id']]);
                $sid = $pdo->lastInsertId();
                $pdo->prepare("INSERT IGNORE INTO session_participants(session_id,user_id,status) VALUES(?,?,'attending')")->execute([$sid,$_SESSION['user_id']]);
                $success = 'Session ajoutée avec succès !';
            }
        }
    }
}

// Sessions à venir
$upcoming = $pdo->query("
    SELECT s.*, u.username,
           COUNT(sp.id) AS pcount
    FROM sessions s
    LEFT JOIN users u ON s.created_by = u.id
    LEFT JOIN session_participants sp ON sp.session_id=s.id AND sp.status='attending'
    WHERE s.status='upcoming' AND s.session_date >= CURDATE()
    GROUP BY s.id
    ORDER BY s.session_date ASC
")->fetchAll();

// Dates pour le calendrier
$dates = $pdo->query("SELECT session_date FROM sessions WHERE status='upcoming'")->fetchAll(PDO::FETCH_COLUMN);

// Films disponibles pour la liste déroulante
$movies_list = $pdo->query("SELECT id, title, poster, year FROM movie_suggestions ORDER BY votes DESC, title ASC")->fetchAll();

$cy = intval($_GET['year'] ?? date('Y'));
$cm = intval($_GET['month'] ?? date('m'));
if($cm<1){$cm=12;$cy--;}
if($cm>12){$cm=1;$cy++;}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Planning - CineClub</title>
    <link rel="stylesheet" href="/cineclub/css/style.css">
    <style>
        /* Film picker dans le modal */
        .movie-picker-input {
            width:100%; padding:11px 13px;
            background:var(--bg3); border:1px solid var(--border);
            border-radius:4px; color:#fff; font-size:13px;
            transition:border-color .18s; cursor:pointer;
        }
        .movie-picker-input:focus { outline:none; border-color:var(--red); }
        .movie-picker-input::placeholder { color:var(--text3); }

        .movie-picker-drop {
            position:absolute; top:100%; left:0; right:0;
            background:#1a1a1a; border:1px solid var(--border);
            border-radius:0 0 4px 4px;
            max-height:300px; overflow-y:auto; z-index:200;
            display:none;
        }
        .movie-picker-drop.open { display:block; }

        .mp-item {
            display:flex; align-items:center; gap:12px;
            padding:10px 12px; cursor:pointer;
            border-bottom:1px solid var(--border);
            transition:background .12s;
        }
        .mp-item:last-child { border-bottom:none; }
        .mp-item:hover { background:var(--bg4); }
        .mp-item.selected { background:rgba(229,9,20,.15); border-left:3px solid var(--red); }

        .mp-img {
            width:38px; height:56px; object-fit:cover;
            border-radius:3px; flex-shrink:0; background:var(--bg4);
        }
        .mp-info strong { font-size:13px; display:block; font-weight:600; }
        .mp-info span   { font-size:11px; color:var(--text3); }

        .mp-wrap { position:relative; }
        .mp-selected-preview {
            display:none; align-items:center; gap:10px;
            margin-top:8px; padding:10px 12px;
            background:rgba(229,9,20,.1); border:1px solid rgba(229,9,20,.3);
            border-radius:4px;
        }
        .mp-selected-preview.show { display:flex; }
        .mp-selected-preview img { width:32px; height:48px; object-fit:cover; border-radius:3px; }
        .mp-selected-preview span { font-size:13px; font-weight:600; flex:1; }
        .mp-selected-preview button { background:none; border:none; color:var(--text3); cursor:pointer; font-size:16px; }
        .mp-selected-preview button:hover { color:#fff; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="page-body">
<div class="container">

    <div class="page-header">
        <div>
            <h1 class="page-title">PLANNING & <span>CALENDAR</span></h1>
            <p class="subtitle">Schedule and view upcoming movie sessions</p>
        </div>
        <?php if(isset($_SESSION['role']) && $_SESSION['role']==='organizer'): ?>
        <button class="btn-red" onclick="openPlanModal()">+ Add Session</button>
        <?php endif; ?>
    </div>

    <?php if($success): ?>
    <div class="alert alert-success">✓ <?= $success ?></div>
    <?php endif; ?>
    <?php foreach($errors as $e): ?>
    <div class="alert alert-error">⚠ <?= $e ?></div>
    <?php endforeach; ?>

    <div class="planning-grid">

        <!-- Calendrier -->
        <div class="card cal-box">
            <div class="cal-top">
                <a href="?month=<?=$cm-1?>&year=<?=$cy?>" class="cal-nav-btn">‹</a>
                <h2><?= date('F Y', mktime(0,0,0,$cm,1,$cy)) ?></h2>
                <a href="?month=<?=$cm+1?>&year=<?=$cy?>" class="cal-nav-btn">›</a>
            </div>
            <div class="cal-grid7">
                <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                <div class="cal-dn"><?=$d?></div>
                <?php endforeach; ?>
                <?php
                $fd = date('w', mktime(0,0,0,$cm,1,$cy));
                $dm = date('t', mktime(0,0,0,$cm,1,$cy));
                $today = date('Y-m-d');
                for($i=0;$i<$fd;$i++) echo '<div></div>';
                for($d=1;$d<=$dm;$d++){
                    $ds = sprintf('%04d-%02d-%02d',$cy,$cm,$d);
                    $cls = 'cal-day';
                    if($ds===$today) $cls.=' today';
                    if(in_array($ds,$dates)) $cls.=' has-s';
                    echo "<div class='$cls'>$d";
                    if(in_array($ds,$dates)) echo "<div class='cal-dot'></div>";
                    echo "</div>";
                }
                ?>
            </div>
            <div class="cal-legend">
                <span><span class="cal-legend-dot" style="background:var(--red);display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px"></span>Session</span>
                <span><span class="cal-legend-dot" style="background:transparent;border:2px solid var(--red);display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px"></span>Today</span>
            </div>
        </div>

        <!-- Sessions -->
        <div>
            <h2 class="section-title" style="margin-bottom:14px">UPCOMING <span>SESSIONS</span></h2>

            <?php if(empty($upcoming)): ?>
            <div class="empty-state">
                <div class="empty-icon">📅</div>
                <h3>No sessions yet</h3>
                <?php if(isset($_SESSION['role']) && $_SESSION['role']==='organizer'): ?>
                <p>Ajoute la première session !</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="sess-list">
                <?php foreach($upcoming as $i=>$s): ?>
                <div class="card sess-card <?=$i===0?'next-s':''?>">
                    <span class="badge <?=$i===0?'sess-badge-next':'sess-badge-planned'?>"><?=$i===0?'NEXT':'PLANNED'?></span>
                    <div class="sess-title"><?= htmlspecialchars(strtoupper($s['movie_title'])) ?></div>
                    <div class="sess-meta">
                        <span>
                            <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5C3.89 3 3 3.9 3 5v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                            <?= date('l, F j, Y', strtotime($s['session_date'])) ?>
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm.5 5v5.3l4.5 2.7-.7 1.2L11 13V7z"/></svg>
                            <?= substr($s['session_time'],0,5) ?>
                        </span>
                        <?php if(!empty($s['location'])): ?>
                        <span>
                            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            <?= htmlspecialchars($s['location']) ?>
                        </span>
                        <?php endif; ?>
                        <span>
                            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                            <?= $s['pcount'] ?> attending
                        </span>
                    </div>
                    <div class="sess-actions">
                        <a href="/cineclub/participants.php" class="btn-ghost" style="font-size:12px;padding:6px 12px">Participants</a>
                        <a href="/cineclub/snacks.php" class="btn-ghost" style="font-size:12px;padding:6px 12px">Snacks</a>
                        <?php if(isset($_SESSION['role']) && $_SESSION['role']==='organizer'): ?>
                        <form method="POST" action="/cineclub/actions/delete_session.php" style="margin:0">
                            <input type="hidden" name="session_id" value="<?=$s['id']?>">
                            <button type="submit" class="btn-ghost" style="font-size:12px;padding:6px 12px;color:#ff6b6b;border-color:rgba(255,107,107,.3)">🗑</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>
</div>

<!-- Modal Add Session -->
<?php if(isset($_SESSION['role']) && $_SESSION['role']==='organizer'): ?>
<div id="m-plan" class="modal-bg" style="display:none"
     onclick="if(event.target===this)this.style.display='none'">
    <div class="modal" style="max-width:520px">
        <h2>📅 Add Session</h2>

        <?php if(!empty($movies_list)): ?>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="movie_id" id="selected-movie-id" value="">

            <!-- Film picker avec affiche -->
            <div class="form-field">
                <label>Film * — choisir depuis Voting</label>
                <div class="mp-wrap">
                    <input type="text" id="mp-search" class="movie-picker-input"
                           placeholder="Rechercher un film suggéré..."
                           oninput="filterMovies(this.value)"
                           onfocus="openMoviePicker()"
                           autocomplete="off">
                    <div class="movie-picker-drop" id="mp-drop">
                        <?php foreach($movies_list as $m): ?>
                        <div class="mp-item"
                             data-id="<?= $m['id'] ?>"
                             data-title="<?= htmlspecialchars($m['title']) ?>"
                             data-poster="<?= htmlspecialchars($m['poster']) ?>"
                             data-year="<?= $m['year'] ?>"
                             onclick="selectMovie(this)">
                            <img class="mp-img"
                                 src="/cineclub/<?= htmlspecialchars($m['poster']) ?>"
                                 onerror="this.src='/cineclub/uploads/posters/default.jpg'"
                                 alt="<?= htmlspecialchars($m['title']) ?>">
                            <div class="mp-info">
                                <strong><?= htmlspecialchars($m['title']) ?></strong>
                                <span><?= $m['year'] ?> · ⭐ votes</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Aperçu film sélectionné -->
                <div class="mp-selected-preview" id="mp-preview">
                    <img id="mp-prev-img" src="" alt="">
                    <span id="mp-prev-title"></span>
                    <button type="button" onclick="clearMovie()" title="Changer">✕</button>
                </div>
            </div>

            <div class="form-field">
                <label>Date *</label>
                <input type="date" name="session_date" min="<?= date('Y-m-d') ?>" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-field">
                    <label>Heure</label>
                    <input type="time" name="session_time" value="20:00">
                </div>
                <div class="form-field">
                    <label>Lieu</label>
                    <input type="text" name="location" placeholder="Ex: Chez Alice, Salle commune...">
                </div>
            </div>
            <div class="modal-btns">
                <button type="submit" class="btn-red" id="submit-btn" disabled
                        style="opacity:.5;cursor:not-allowed">
                    Add Session
                </button>
                <button type="button" class="btn-dark"
                    onclick="document.getElementById('m-plan').style.display='none'">
                    Cancel
                </button>
            </div>
        </form>
        <?php else: ?>
        <div style="text-align:center;padding:24px;color:var(--text3)">
            <p style="font-size:32px;margin-bottom:12px">🎬</p>
            <p style="font-size:14px">Aucun film dans Voting pour le moment.</p>
            <p style="font-size:12px;margin-top:6px">Suggère d'abord des films depuis la page <a href="/cineclub/voting.php" style="color:var(--red)">Voting</a>.</p>
        </div>
        <div class="modal-btns" style="justify-content:flex-end">
            <button type="button" class="btn-dark"
                onclick="document.getElementById('m-plan').style.display='none'">
                Fermer
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function openPlanModal() {
    document.getElementById('m-plan').style.display = 'flex';
    // Réinitialiser
    clearMovie();
    document.getElementById('mp-search').value = '';
    filterMovies('');
}

function openMoviePicker() {
    document.getElementById('mp-drop').classList.add('open');
}

function filterMovies(q) {
    const drop = document.getElementById('mp-drop');
    drop.classList.add('open');
    const items = drop.querySelectorAll('.mp-item');
    const low = q.toLowerCase();
    items.forEach(item => {
        const title = item.dataset.title.toLowerCase();
        item.style.display = (!low || title.includes(low)) ? '' : 'none';
    });
}

function selectMovie(el) {
    const id     = el.dataset.id;
    const title  = el.dataset.title;
    const poster = el.dataset.poster;

    document.getElementById('selected-movie-id').value = id;
    document.getElementById('mp-search').value = title;
    document.getElementById('mp-drop').classList.remove('open');

    // Aperçu
    const prev = document.getElementById('mp-preview');
    document.getElementById('mp-prev-img').src = '/cineclub/' + poster;
    document.getElementById('mp-prev-img').onerror = function(){ this.src='/cineclub/uploads/posters/default.jpg'; };
    document.getElementById('mp-prev-title').textContent = title;
    prev.classList.add('show');

    // Activer le bouton submit
    const btn = document.getElementById('submit-btn');
    btn.disabled = false;
    btn.style.opacity = '1';
    btn.style.cursor = 'pointer';

    // Highlight
    document.querySelectorAll('.mp-item').forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
}

function clearMovie() {
    document.getElementById('selected-movie-id').value = '';
    document.getElementById('mp-search').value = '';
    document.getElementById('mp-preview').classList.remove('show');
    document.querySelectorAll('.mp-item').forEach(i => i.classList.remove('selected'));
    const btn = document.getElementById('submit-btn');
    if(btn){ btn.disabled = true; btn.style.opacity = '0.5'; btn.style.cursor = 'not-allowed'; }
}

// Fermer dropdown au clic extérieur
document.addEventListener('click', function(e) {
    if(!e.target.closest('.mp-wrap')) {
        const drop = document.getElementById('mp-drop');
        if(drop) drop.classList.remove('open');
    }
});
</script>

<?php if(!empty($errors)): ?>
<script>document.getElementById('m-plan').style.display='flex';</script>
<?php endif; ?>
<?php endif; ?>

</body>
</html>