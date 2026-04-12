<?php
require 'config/db.php';
session_start();

$sess = $pdo->query("SELECT * FROM sessions WHERE status='upcoming' ORDER BY session_date ASC LIMIT 1")->fetch();
$snacks = [];
if ($sess) {
    $s = $pdo->prepare("SELECT s.*, u.username AS aname FROM snacks s LEFT JOIN users u ON u.id=s.assigned_to WHERE s.session_id=? ORDER BY s.id ASC");
    $s->execute([$sess['id']]);
    $snacks = $s->fetchAll();
}
$nc = count(array_filter($snacks, fn($s) => $s['status']==='confirmed'));
$np = count(array_filter($snacks, fn($s) => $s['status']==='pending'));
$nu = count(array_filter($snacks, fn($s) => $s['status']==='unassigned'));
$isOrg = isset($_SESSION['role']) && $_SESSION['role'] === 'organizer';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Snacks - CineClub</title>
    <link rel="stylesheet" href="/cineclub/css/style.css">
    <style>
        .btn-del-snack {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-top: 6px;
            padding: 7px;
            background: transparent;
            color: rgba(255, 100, 100, 0.65);
            border: 1px solid rgba(229, 9, 20, 0.25);
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all .18s;
            gap: 5px;
        }
        .btn-del-snack:hover {
            background: rgba(229, 9, 20, 0.12);
            color: #ff6b6b;
            border-color: rgba(229, 9, 20, 0.5);
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="page-body">
<div class="container">

    <div class="page-header">
        <div>
            <h1 class="page-title">SNACK <span>STATION</span></h1>
            <p class="subtitle">Organize who brings what for movie night</p>
        </div>
        <?php if ($isOrg): ?>
        <button class="btn-red" onclick="document.getElementById('m-snack').style.display='flex'">
            + Add Snack
        </button>
        <?php endif; ?>
    </div>

    <?php if (!empty($snacks)): ?>
    <div class="status-row">
        <span class="badge badge-green">✓ <?= $nc ?> confirmed</span>
        <span class="badge badge-orange">⏳ <?= $np ?> pending</span>
        <span class="badge badge-dark">⚠ <?= $nu ?> unassigned</span>
    </div>
    <?php endif; ?>

    <?php if (!$sess): ?>
    <div class="empty-state">
        <div class="empty-icon">🍿</div>
        <h3>No session yet</h3>
        <p>Crée une session depuis la page Planning.</p>
    </div>

    <?php elseif (empty($snacks)): ?>
    <div class="empty-state">
        <div class="empty-icon">🍿</div>
        <h3>No snacks yet</h3>
        <?php if ($isOrg): ?>
        <p>Ajoute les snacks pour la prochaine soirée !</p>
        <button class="btn-red" style="margin-top:14px"
            onclick="document.getElementById('m-snack').style.display='flex'">
            + Add First Snack
        </button>
        <?php else: ?>
        <p>L'organisateur n'a pas encore ajouté de snacks.</p>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="snacks-grid">
        <?php foreach ($snacks as $sn): ?>
        <div class="card snack-card">
            <div class="snack-top">
                <span class="snack-emoji"><?= htmlspecialchars($sn['emoji']) ?></span>
                <?php if ($sn['status'] === 'confirmed'): ?>
                    <span class="badge badge-green">✓ Confirmed</span>
                <?php elseif ($sn['status'] === 'pending'): ?>
                    <span class="badge badge-orange">⏳ Pending</span>
                <?php else: ?>
                    <span class="badge badge-dark">⚠ Unassigned</span>
                <?php endif; ?>
            </div>

            <div class="snack-name"><?= htmlspecialchars($sn['name']) ?></div>

            <?php if ($sn['aname']): ?>
                <p class="snack-by">Brought by <strong><?= htmlspecialchars($sn['aname']) ?></strong></p>
                <?php if ($isOrg && $sn['status'] === 'pending'): ?>
                <form method="POST" action="/cineclub/actions/confirm_snack.php">
                    <input type="hidden" name="snack_id" value="<?= $sn['id'] ?>">
                    <button class="btn-conf">✓ Confirm</button>
                </form>
                <?php endif; ?>
            <?php else: ?>
                <p class="snack-none">No one assigned yet</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" action="/cineclub/actions/volunteer_snack.php">
                    <input type="hidden" name="snack_id" value="<?= $sn['id'] ?>">
                    <button class="btn-vol">🙋 Volunteer</button>
                </form>
                <?php else: ?>
                <a href="/cineclub/welcome.php" class="btn-vol">🔐 Login to volunteer</a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Bouton supprimer (organisateur seulement) -->
            <?php if ($isOrg): ?>
            <form method="POST" action="/cineclub/actions/delete_snack.php">
                <input type="hidden" name="snack_id" value="<?= $sn['id'] ?>">
                <button type="submit" class="btn-del-snack">
                    🗑 Delete snack
                </button>
            </form>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</div>

<!-- Modal Add Snack -->
<div id="m-snack" class="modal-bg" style="display:none"
     onclick="if(event.target===this)this.style.display='none'">
    <div class="modal">
        <h2>🍿 Add a Snack</h2>
        <form method="POST" action="/cineclub/actions/add_snack.php">
            <input type="hidden" name="session_id" value="<?= $sess['id'] ?? 0 ?>">
            <input type="hidden" name="emoji" id="e-val" value="🍿">

            <div class="form-field">
                <label>Nom du snack *</label>
                <input type="text" name="name" id="sn-name"
                       placeholder="Pizza, Popcorn, Nachos..."
                       oninput="suggestEmoji(this.value)" required>
            </div>

            <!-- Suggestion emoji -->
            <div class="emoji-sug" id="e-sug">
                <span class="sug-emoji" id="e-icon">🍿</span>
                <div class="sug-text">
                    <strong id="e-name">Popcorn</strong>
                    Emoji suggéré automatiquement
                </div>
                <button type="button" class="sug-ok" onclick="acceptEmoji()">✓ OK</button>
            </div>

            <div class="modal-btns">
                <button type="submit" class="btn-red">Add Snack</button>
                <button type="button" class="btn-dark"
                    onclick="document.getElementById('m-snack').style.display='none'">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const emap = {
    'pizza':'🍕','popcorn':'🍿','nachos':'🧀','chips':'🥔','frites':'🍟',
    'burger':'🍔','hotdog':'🌭','hot dog':'🌭','sandwich':'🥪','tacos':'🌮',
    'sushi':'🍣','ramen':'🍜','pates':'🍝','riz':'🍚','soupe':'🍲',
    'salade':'🥗','glace':'🍦','ice cream':'🍨','gateau':'🎂','cake':'🍰',
    'cookie':'🍪','biscuit':'🍪','chocolat':'🍫','bonbons':'🍬','candy':'🍬',
    'donut':'🍩','donuts':'🍩','croissant':'🥐','fromage':'🧀','cheese':'🧀',
    'coca':'🥤','soda':'🥤','jus':'🧃','juice':'🧃','eau':'💧','water':'💧',
    'cafe':'☕','coffee':'☕','the':'🍵','tea':'🍵','biere':'🍺','beer':'🍺',
    'vin':'🍷','wine':'🍷','cocktail':'🍹','smoothie':'🥤','milkshake':'🥤',
    'pomme':'🍎','banane':'🍌','raisin':'🍇','fraise':'🍓','orange':'🍊',
    'citron':'🍋','ananas':'🍍','pastèque':'🍉','mangue':'🥭','avocat':'🥑',
    'carotte':'🥕','mais':'🌽','corn':'🌽','noisette':'🌰','cacahuete':'🥜',
    'peanut':'🥜','nutella':'🍫','muffin':'🧁','cupcake':'🧁','brownie':'🍫',
    'mix':'🍬','pretzel':'🥨','granola':'🥣','cereales':'🥣',
};
let curEmoji = '🍿';

function suggestEmoji(t) {
    const low = t.toLowerCase().trim();
    const box = document.getElementById('e-sug');
    if (!low) { box.classList.remove('show'); return; }
    let found = null;
    for (const [k, e] of Object.entries(emap)) {
        if (low.includes(k) || k.includes(low)) { found = {k, e}; break; }
    }
    if (found) {
        curEmoji = found.e;
        document.getElementById('e-icon').textContent = found.e;
        document.getElementById('e-name').textContent = found.k.charAt(0).toUpperCase() + found.k.slice(1);
        document.getElementById('e-val').value = found.e;
        box.classList.add('show');
    } else {
        document.getElementById('e-val').value = '🍿';
        box.classList.remove('show');
    }
}
function acceptEmoji() {
    document.getElementById('e-val').value = curEmoji;
    document.getElementById('e-sug').classList.remove('show');
}
</script>
</body>
</html>
