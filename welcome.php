<?php
require 'config/db.php';
session_start();
if (isset($_SESSION['user_id'])) { header("Location: /cineclub/index.php"); exit; }

$error   = $_GET['error']   ?? '';
$success = $_GET['success'] ?? '';
$tab     = $_GET['tab']     ?? 'login';

$errors = [
    'wrong'          => 'Email ou mot de passe incorrect.',
    'wrong_role'     => 'Ce compte n\'appartient pas à ce rôle. Vérifiez l\'onglet sélectionné.',
    'empty'          => 'Veuillez remplir tous les champs.',
    'not_organizer'  => 'Aucun organisateur trouvé avec cet email.',
    'not_member'     => 'Aucun membre trouvé. Utilisez un code d\'invitation.',
    'short_password' => 'Mot de passe trop court (min 6 caractères).',
    'email_taken'    => 'Cet email est déjà utilisé.',
    'invalid_code'   => 'Code d\'invitation invalide ou expiré.',
    'organizer_exists' => 'Un organisateur existe déjà. Connectez-vous.',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineClub — Organisez vos soirées cinéma</title>
    <link rel="stylesheet" href="/cineclub/css/style.css">
</head>
<body class="welcome-page">

<!-- ─── HERO ─── -->
<section class="welcome-hero">
    <div class="welcome-hero-bg"></div>

    <!-- Navbar welcome -->
    <div class="welcome-hero-nav">
        <div class="welcome-hero-nav-logo">CINECLUB</div>
    </div>

    <!-- Contenu centré -->
    <div class="welcome-hero-content">
        <h1>Vos soirées cinéma,<br>organisées ensemble</h1>
        <h2>Votez · Planifiez · Snackez</h2>
        <p>Invitez vos amis, choisissez le film, gérez les snacks.<br>Tout en un seul endroit. Gratuit.</p>

        <!-- LOGIN/REGISTER inline -->
        <div class="welcome-login-box">

            <?php if ($error): ?>
            <div class="wl-error"><?= htmlspecialchars($errors[$error] ?? 'Erreur.') ?></div>
            <?php endif; ?>
            <?php if ($success === 'registered'): ?>
            <div class="wl-success">✓ Compte créé ! Connectez-vous.</div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="wl-tabs">
                <button class="wl-tab <?=$tab==='login'?'active':''?>" onclick="setTab('login')">Se connecter</button>
                <button class="wl-tab <?=$tab==='register'?'active':''?>" onclick="setTab('register')">Créer un compte</button>
            </div>

            <!-- ── LOGIN FORM ── -->
            <div id="form-login" <?=$tab!=='login'?'style="display:none"':''?>>
                <div class="wl-tabs" style="margin-bottom:14px">
                    <button class="wl-tab active" id="lt-org" onclick="setLoginRole('organizer')">👑 Organisateur</button>
                    <button class="wl-tab" id="lt-mem" onclick="setLoginRole('member')">👤 Membre</button>
                </div>
                <form method="POST" action="/cineclub/actions/login_action.php">
                    <input type="hidden" name="login_type" id="lt-type" value="organizer">
                    <input class="wl-input" type="email" name="email" placeholder="Email" required>
                    <div class="wl-pwd">
                        <input class="wl-input" type="password" name="password" id="lp-pwd" placeholder="Mot de passe" required style="margin-bottom:0">
                        <button type="button" class="wl-pwd-toggle" onclick="tog('lp-pwd',this)">👁</button>
                    </div>
                    <button type="submit" class="wl-submit" id="lt-btn">Se connecter</button>
                </form>
                <p class="wl-footer">Pas encore de compte ? <a href="#" onclick="setTab('register');return false">Créer un compte</a></p>
            </div>

            <!-- ── REGISTER FORM ── -->
            <div id="form-register" <?=$tab!=='register'?'style="display:none"':''?>>
                <div class="wl-tabs" style="margin-bottom:14px">
                    <button class="wl-tab active" id="rt-org" onclick="setRegRole('organizer')">👑 Organisateur</button>
                    <button class="wl-tab" id="rt-mem" onclick="setRegRole('member')">👤 Membre</button>
                </div>
                <div id="rt-org-desc" style="font-size:12px;color:rgba(255,255,255,.45);margin-bottom:10px;">Tu organiseras les soirées et inviteras les membres.</div>
                <div id="rt-mem-desc" style="font-size:12px;color:rgba(255,255,255,.45);margin-bottom:10px;display:none;">Tu as reçu un code d'invitation de l'organisateur.</div>
                <form method="POST" action="/cineclub/actions/register_action.php">
                    <input type="hidden" name="role" id="rt-role" value="organizer">
                    <input class="wl-input" type="text"  name="username" placeholder="Prénom" required>
                    <input class="wl-input" type="email" name="email"    placeholder="Email"  required>
                    <div class="wl-pwd">
                        <input class="wl-input" type="password" name="password" id="rp-pwd" placeholder="Mot de passe (min 6)" required style="margin-bottom:0">
                        <button type="button" class="wl-pwd-toggle" onclick="tog('rp-pwd',this)">👁</button>
                    </div>
                    <div id="rt-code-field" style="display:none">
                        <input class="wl-input" type="text" name="invite_code" id="rt-code" placeholder="Code d'invitation (ex: AB3K9XZ2)" maxlength="8" style="text-transform:uppercase;letter-spacing:4px;text-align:center;font-size:16px;font-weight:700">
                    </div>
                    <button type="submit" class="wl-submit">Créer mon compte</button>
                </form>
                <p class="wl-footer">Déjà membre ? <a href="#" onclick="setTab('login');return false">Se connecter</a></p>
            </div>

        </div>
    </div>
</section>

<!-- Divider -->
<div class="welcome-divider"></div>

<!-- Features -->
<section class="welcome-features">
    <div class="wf-card">
        <h3>Votez pour le prochain film</h3>
        <p>Suggérez des films et votez avec vos amis. Le plus voté est sélectionné.</p>
        <div class="wf-icon">🗳</div>
    </div>
    <div class="wf-card">
        <h3>Organisez les snacks</h3>
        <p>Chaque membre se porte volontaire pour un snack. L'organisateur confirme.</p>
        <div class="wf-icon">🍿</div>
    </div>
    <div class="wf-card">
        <h3>Invitez vos amis</h3>
        <p>L'organisateur génère un code unique. Vos amis rejoignent en quelques secondes.</p>
        <div class="wf-icon">🎟</div>
    </div>
    <div class="wf-card">
        <h3>Revivez les soirées</h3>
        <p>Archives, notes, photos. Chaque soirée laisse une trace inoubliable.</p>
        <div class="wf-icon">🎬</div>
    </div>
</section>

<div class="welcome-divider"></div>

<!-- FAQ -->
<section class="welcome-faq">
    <h2>Questions fréquentes</h2>
    <?php
    $faqs = [
        ["C'est gratuit ?","Oui, CineClub est entièrement gratuit. Créez un compte et organisez votre première soirée."],
        ["Comment inviter des amis ?","Depuis la page Participants, générez un code d'invitation. Partagez-le par WhatsApp ou SMS. Vos amis l'utilisent lors de leur inscription."],
        ["Comment voter pour un film ?","Depuis la page Voting, cliquez sur Vote. Cliquez à nouveau pour retirer votre vote."],
        ["Peut-on modifier les snacks ?","L'organisateur ajoute les snacks. Les membres se portent volontaires. L'organisateur confirme chaque participation."],
    ];
    foreach ($faqs as $i => $f):
    ?>
    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(<?=$i?>)">
            <span><?=$f[0]?></span>
            <span class="faq-icon" id="fi-<?=$i?>">+</span>
        </div>
        <div class="faq-a" id="fa-<?=$i?>"><?=$f[1]?></div>
    </div>
    <?php endforeach; ?>
    <div class="faq-end"></div>
</section>

<!-- CTA -->
<div class="welcome-cta">
    <div>
        <p>Prêt pour votre prochaine soirée cinéma ?</p>
        <span>Rejoignez CineClub gratuitement dès maintenant.</span>
    </div>
    <div style="display:flex;gap:10px">
        <a href="#" onclick="setTab('register');document.querySelector('.welcome-hero-content').scrollIntoView({behavior:'smooth'});return false" class="btn-red">Créer un compte</a>
        <a href="#" onclick="setTab('login');document.querySelector('.welcome-hero-content').scrollIntoView({behavior:'smooth'});return false" class="btn-dark">Se connecter</a>
    </div>
</div>

<footer style="background:#000;padding:22px 60px;border-top:1px solid #222;">
    <p style="color:#555;font-size:13px;text-align:center;">© 2026 CineClub — Organisez des soirées cinéma inoubliables.</p>
</footer>

<script>
function setTab(t) {
    document.getElementById('form-login').style.display    = t==='login'    ? '' : 'none';
    document.getElementById('form-register').style.display = t==='register' ? '' : 'none';
    document.querySelectorAll('.wl-tab').forEach((b,i)=>{
        if(i<2) b.classList.toggle('active', (t==='login'&&i===0)||(t==='register'&&i===1));
    });
}
function setLoginRole(r) {
    document.getElementById('lt-type').value = r;
    document.getElementById('lt-org').classList.toggle('active', r==='organizer');
    document.getElementById('lt-mem').classList.toggle('active', r==='member');
    document.getElementById('lt-btn').textContent = r==='organizer' ? 'Se connecter (organisateur)' : 'Se connecter (membre)';
}
function setRegRole(r) {
    document.getElementById('rt-role').value = r;
    document.getElementById('rt-org').classList.toggle('active', r==='organizer');
    document.getElementById('rt-mem').classList.toggle('active', r==='member');
    document.getElementById('rt-org-desc').style.display = r==='organizer' ? '' : 'none';
    document.getElementById('rt-mem-desc').style.display = r==='member'    ? '' : 'none';
    document.getElementById('rt-code-field').style.display = r==='member'  ? '' : 'none';
    if(r==='member') document.getElementById('rt-code').setAttribute('required','');
    else document.getElementById('rt-code').removeAttribute('required');
}
function tog(id, btn) {
    var i=document.getElementById(id);
    i.type = i.type==='password'?'text':'password';
}
function toggleFaq(i) {
    var a=document.getElementById('fa-'+i), ic=document.getElementById('fi-'+i);
    var open = a.classList.contains('open');
    a.classList.toggle('open',!open);
    ic.classList.toggle('open',!open);
    ic.textContent = open ? '+' : '×';
}
<?php if($error && in_array($error,['not_member','wrong_role','invalid_code'])): ?>
setTab('login'); setLoginRole('member');
<?php elseif($error === 'organizer_exists'): ?>
setTab('login'); setLoginRole('organizer');
<?php elseif($tab==='register'): ?>
setTab('register');
<?php endif; ?>
</script>
</body>
</html>
