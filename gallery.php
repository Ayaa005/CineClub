<?php
require 'config/db.php';
session_start();

$photos = $pdo->query("
    SELECT g.*, s.movie_title, u.username AS uploader
    FROM gallery g
    LEFT JOIN sessions s ON s.id = g.session_id
    LEFT JOIN users u ON u.id = g.uploaded_by
    ORDER BY g.id DESC
")->fetchAll();

$isConnected = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Gallery - CineClub</title>
    <link rel="stylesheet" href="/cineclub/css/style.css">
    <style>
        .gal-card {
            border-radius: 4px;
            overflow: hidden;
            position: relative;
            background: var(--bg2);
            cursor: pointer;
            transition: transform .2s;
        }
        .gal-card:hover { transform: scale(1.03); z-index: 2; box-shadow: 0 6px 24px rgba(0,0,0,.7); }
        .gal-card img { width: 100%; height: 190px; object-fit: cover; display: block; }
        .gal-overlay {
            position: absolute; bottom: 0; left: 0; right: 0;
            background: linear-gradient(180deg, transparent, rgba(0,0,0,.92));
            padding: 28px 10px 10px;
            opacity: 0; transition: opacity .18s;
            display: flex; flex-direction: column; gap: 6px;
        }
        .gal-card:hover .gal-overlay { opacity: 1; }
        .gal-overlay strong { font-size: 13px; font-weight: 600; display: block; }
        .gal-overlay span   { font-size: 11px; color: var(--text2); }
        .btn-del-photo {
            display: flex; align-items: center; justify-content: center;
            gap: 4px; padding: 5px 10px;
            background: rgba(229,9,20,.2); color: #ff6b6b;
            border: 1px solid rgba(229,9,20,.4); border-radius: 3px;
            font-size: 11px; font-weight: 600; cursor: pointer;
            transition: all .15s; margin-top: 4px; width: 100%;
        }
        .btn-del-photo:hover { background: rgba(229,9,20,.35); color: #fff; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="page-body">
<div class="container">

    <div class="page-header">
        <div>
            <h1 class="page-title">GALL<span>ERY</span></h1>
            <p class="subtitle">Photos from our movie nights</p>
        </div>
        <?php if ($isConnected): ?>
        <button class="btn-red" onclick="document.getElementById('m-upload').style.display='flex'">
            📷 Upload Photo
        </button>
        <?php endif; ?>
    </div>

    <?php if (empty($photos)): ?>
    <div class="empty-state">
        <div class="empty-icon">🖼</div>
        <h3>No photos yet</h3>
        <p>Partage les photos de vos soirées cinéma !</p>
    </div>
    <?php else: ?>
    <div class="gal-grid">
        <?php foreach ($photos as $p): ?>
        <div class="gal-card">
            <img src="/cineclub/<?= htmlspecialchars($p['image_path']) ?>"
                 onerror="this.src='/cineclub/uploads/gallery/default.jpg'"
                 alt="<?= htmlspecialchars($p['caption'] ?? '') ?>">
            <div class="gal-overlay">
                <strong><?= htmlspecialchars($p['caption'] ?? 'Photo') ?></strong>
                <span><?= htmlspecialchars($p['movie_title'] ?? '') ?></span>

                <!-- Bouton supprimer pour tous les connectés -->
                <?php if ($isConnected): ?>
                <form method="POST" action="/cineclub/actions/delete_photo.php">
                    <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn-del-photo">🗑 Delete</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</div>

<!-- Modal Upload -->
<?php if ($isConnected): ?>
<div id="m-upload" class="modal-bg" style="display:none"
     onclick="if(event.target===this)this.style.display='none'">
    <div class="modal">
        <h2>📷 Upload Photo</h2>
        <p style="font-size:13px;color:var(--text2);margin-bottom:14px">
            Sélectionne une image depuis n'importe quel dossier de ton ordinateur.
        </p>
        <form method="POST" action="/cineclub/actions/upload_photo.php"
              enctype="multipart/form-data">
            <div class="form-field">
                <label>Photo * (JPG, PNG, WEBP, GIF)</label>
                <!-- accept="image/*" permet de parcourir tous les dossiers -->
                <input type="file" name="photo" accept="image/*" required
                       style="padding:8px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;color:#fff;width:100%;cursor:pointer;">
            </div>
            <div class="form-field">
                <label>Légende (optionnel)</label>
                <input type="text" name="caption" placeholder="Movie night setup...">
            </div>
            <div class="modal-btns">
                <button type="submit" class="btn-red">Upload</button>
                <button type="button" class="btn-dark"
                    onclick="document.getElementById('m-upload').style.display='none'">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

</body>
</html>
