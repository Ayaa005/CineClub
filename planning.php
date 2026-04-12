<?php
// =============================================
//  planning.php — Planning & Calendar
//  Tables utilisées :
//    sessions (id, movie_title, movie_poster, session_date, session_time, status, created_by)
//    session_participants (id, session_id, user_id, status)
//    users (id, username, email, password, role)
// =============================================
require_once 'config.php';
requireLogin();

$errors  = [];
$success = '';

// =============================================
//  AJOUTER UNE SESSION
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_session') {

    $movie_title  = trim($_POST['movie_title']  ?? '');
    $session_date = $_POST['session_date']       ?? '';
    $session_time = $_POST['session_time']       ?? '20:00';
    // movie_poster : upload optionnel
    $movie_poster = 'uploads/posters/default.jpeg';

    if (!empty($_FILES['movie_poster']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['movie_poster']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed)) {
            $dest = 'uploads/posters/' . uniqid('poster_') . '.' . $ext;
            if (move_uploaded_file($_FILES['movie_poster']['tmp_name'], $dest)) {
                $movie_poster = $dest;
            }
        }
    }

    if (empty($movie_title)) {
        $errors[] = "Le titre du film est obligatoire.";
    }
    if (empty($session_date)) {
        $errors[] = "La date est obligatoire.";
    } elseif (strtotime($session_date) < strtotime('today')) {
        $errors[] = "La date doit être dans le futur.";
    }

    if (empty($errors)) {
        // Colonnes réelles de la table sessions :
        // movie_title, movie_poster, session_date, session_time, status, created_by
        $stmt = $conn->prepare(
            "INSERT INTO sessions (movie_title, movie_poster, session_date, session_time, created_by)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssi",
            $movie_title, $movie_poster,
            $session_date, $session_time,
            $_SESSION['user_id']
        );

        if ($stmt->execute()) {
            $success = "Session ajoutée avec succès !";
        } else {
            $errors[] = "Erreur lors de l'ajout : " . $conn->error;
        }
        $stmt->close();
    }
}

// =============================================
//  SUPPRIMER UNE SESSION (organizer uniquement)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_session') {
    if (isOrganizer()) {
        $sid  = intval($_POST['session_id']);
        $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        $stmt->close();
        $success = "Session supprimée.";
    }
}

// =============================================
//  RÉCUPÉRER LES SESSIONS À VENIR
//  JOIN sur session_participants (nom réel de la table)
//  JOIN sur users pour le username du créateur
// =============================================
$sessions_result = $conn->query(
    "SELECT s.*,
            u.username,
            COUNT(p.id) AS participant_count
     FROM sessions s
     LEFT JOIN users u ON s.created_by = u.id
     LEFT JOIN session_participants p ON p.session_id = s.id AND p.status = 'attending'
     WHERE s.status = 'upcoming'
       AND s.session_date >= CURDATE()
     GROUP BY s.id
     ORDER BY s.session_date ASC"
);
$upcoming_sessions = $sessions_result ? $sessions_result->fetch_all(MYSQLI_ASSOC) : [];

// =============================================
//  DATES POUR LE CALENDRIER
// =============================================
$dates_result  = $conn->query(
    "SELECT session_date FROM sessions WHERE status = 'upcoming' ORDER BY session_date ASC"
);
$session_dates = [];
if ($dates_result) {
    while ($row = $dates_result->fetch_assoc()) {
        $session_dates[] = $row['session_date'];
    }
}

// Mois affiché dans le calendrier
$cal_year  = intval($_GET['year']  ?? date('Y'));
$cal_month = intval($_GET['month'] ?? date('m'));
if ($cal_month < 1)  { $cal_month = 12; $cal_year--; }
if ($cal_month > 12) { $cal_month = 1;  $cal_year++; }

$months_en = ['','January','February','March','April','May','June',
              'July','August','September','October','November','December'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="planning.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>CineClub - Planning</title>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="logo">
        <img src="Screenshot_2026-02-12_203643-removebg-preview.png" alt="CineClub Logo">
    </div>
    <ul class="nav-links">
        <li><a href="index.php"><i class="fa-solid fa-house"></i> Home</a></li>
        <li><a href="voting.php"><i class="fa-solid fa-check-to-slot"></i> Voting</a></li>
        <li><a href="planning.php" class="active"><i class="fa-solid fa-calendar"></i> Planning</a></li>
        <li><a href="participants.php"><i class="fa-solid fa-users"></i> Participants</a></li>
        <li><a href="snaks.php"><i class="fa-solid fa-cookie-bite"></i> Snacks</a></li>
        <li><a href="archives.php"><i class="fa-solid fa-box-archive"></i> Archives</a></li>
        <li><a href="gallery.php"><i class="fa-solid fa-image"></i> Gallery</a></li>
    </ul>
    <?php if (isLoggedIn()): ?>
        <div class="user-nav">
            <span class="user-name">
                <i class="fa-solid fa-circle-user"></i>
                <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <a href="logout.php" class="login-btn">Logout</a>
        </div>
    <?php else: ?>
        <a href="login.php" class="login-btn">Login</a>
    <?php endif; ?>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
    <h1><i class="fa-solid fa-calendar"></i> PLANNING & <span>CALENDAR</span></h1>
    <p>Schedule and manage upcoming CineClub sessions</p>
</div>

<main class="planning-main">

    <!-- ===== CALENDAR ===== -->
    <section class="calendar-section">
        <div class="calendar-header">
            <a href="?month=<?= $cal_month - 1 ?>&year=<?= $cal_year ?>" class="nav-btn">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <h2><?= $months_en[$cal_month] . ' ' . $cal_year ?></h2>
            <a href="?month=<?= $cal_month + 1 ?>&year=<?= $cal_year ?>" class="nav-btn">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>

        <div class="calendar-grid">
            <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                <div class="day-label"><?= $d ?></div>
            <?php endforeach; ?>

            <?php
            $first_day     = date('w', mktime(0,0,0,$cal_month,1,$cal_year));
            $days_in_month = date('t', mktime(0,0,0,$cal_month,1,$cal_year));
            $today         = date('Y-m-d');

            for ($i = 0; $i < $first_day; $i++) {
                echo '<div class="day-cell empty"></div>';
            }
            for ($d = 1; $d <= $days_in_month; $d++) {
                $date_str = sprintf('%04d-%02d-%02d', $cal_year, $cal_month, $d);
                $classes  = ['day-cell'];
                if ($date_str === $today)                  $classes[] = 'today';
                if (in_array($date_str, $session_dates))   $classes[] = 'has-session';
                echo '<div class="' . implode(' ', $classes) . '">' . $d . '</div>';
            }
            ?>
        </div>

        <div class="cal-legend">
            <span class="legend-item"><span class="dot red"></span> Session</span>
            <span class="legend-item"><span class="dot today-dot"></span> Today</span>
        </div>
    </section>

    <!-- ===== SESSIONS ===== -->
    <section class="sessions-section">
        <div class="sessions-header">
            <h2>UPCOMING <span>SESSIONS</span></h2>
            <button class="add-btn" id="openModal">
                <i class="fa-solid fa-plus"></i> Add Session
            </button>
        </div>

        <?php if ($success): ?>
            <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert error">
                <?php foreach ($errors as $e): ?>
                    <div><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="sessions-list">
            <?php if (empty($upcoming_sessions)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-film"></i>
                    <p>No upcoming sessions yet. Add the first one!</p>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming_sessions as $i => $s): ?>
                <div class="session-item <?= $i === 0 ? 'next' : '' ?>">

                    <div class="session-badge <?= $i === 0 ? '' : 'planned' ?>">
                        <?= $i === 0 ? 'NEXT' : 'PLANNED' ?>
                    </div>

                    <?php
                    $poster = $s['movie_poster'] ?? '';
                    if ($poster && file_exists($poster)): ?>
                        <img src="<?= htmlspecialchars($poster) ?>" alt="poster" class="session-poster">
                    <?php else: ?>
                        <div class="session-poster placeholder"><i class="fa-solid fa-film"></i></div>
                    <?php endif; ?>

                    <div class="session-details">
                        <h3><?= htmlspecialchars(strtoupper($s['movie_title'])) ?></h3>
                        <div class="session-meta">
                            <span>
                                <i class="fa-solid fa-calendar"></i>
                                <?= date('l, F j, Y', strtotime($s['session_date'])) ?>
                            </span>
                            <span>
                                <i class="fa-solid fa-clock"></i>
                                <?= date('H:i', strtotime($s['session_time'])) ?>
                            </span>
                            <span>
                                <i class="fa-solid fa-user-group"></i>
                                <?= intval($s['participant_count']) ?> attending
                            </span>
                            <?php if (!empty($s['username'])): ?>
                            <span>
                                <i class="fa-solid fa-user"></i>
                                Added by <?= htmlspecialchars($s['username']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="session-actions">
                        <a href="participants.php?session=<?= $s['id'] ?>" class="btn-red">
                            <i class="fa-solid fa-users"></i> Participants
                        </a>
                        <a href="snaks.php?session=<?= $s['id'] ?>" class="btn-dark">
                            <i class="fa-solid fa-cookie-bite"></i> Snacks
                        </a>
                        <?php if (isOrganizer()): ?>
                            <form method="POST" style="margin:0"
                                  onsubmit="return confirm('Delete this session?')">
                                <input type="hidden" name="action"     value="delete_session">
                                <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="btn-delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

</main>

<!-- ===== MODAL ADD SESSION ===== -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-plus"></i> Add New Session</h3>
            <button class="modal-close" id="modalClose"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="planning.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_session">

                <div class="form-group">
                    <label>Movie Title</label>
                    <input type="text" name="movie_title" placeholder="Enter movie title..." required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="session_date" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Time</label>
                        <input type="time" name="session_time" value="20:00">
                    </div>
                </div>

                <div class="form-group">
                    <label>Movie Poster (optional)</label>
                    <input type="file" name="movie_poster" accept="image/*">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-dark" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn-red">
                        <i class="fa-solid fa-plus"></i> Add Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const overlay   = document.getElementById('modalOverlay');
const openBtn   = document.getElementById('openModal');
const closeBtn  = document.getElementById('modalClose');
const cancelBtn = document.getElementById('cancelBtn');

openBtn.addEventListener('click',   () => overlay.style.display = 'flex');
closeBtn.addEventListener('click',  () => overlay.style.display = 'none');
cancelBtn.addEventListener('click', () => overlay.style.display = 'none');
overlay.addEventListener('click',   (e) => { if (e.target === overlay) overlay.style.display = 'none'; });

<?php if (!empty($errors)): ?>
window.addEventListener('load', () => overlay.style.display = 'flex');
<?php endif; ?>
</script>

</body>
</html>
