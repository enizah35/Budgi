<?php
session_start();
require_once __DIR__ . '/../includes/layout.php';

$logged = isset($_SESSION['user_id']);
render_header('Accueil', 'home', $logged);
?>
<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="hero-badge"><span class="icon">school</span>Pensé pour les étudiants · 100% gratuit</span>
            <h1>Ton budget, enfin <span>sous contrôle</span>.</h1>
            <p class="hero-lead">Budgi t'aide à suivre tes dépenses par catégorie et te prévient avant que ton compte ne crie au secours.</p>
            <div class="hero-actions">
                <?php if ($logged): ?>
                    <a href="gestion.php" class="btn btn-primary btn-lg">Mon tableau de bord<span class="icon">arrow_forward</span></a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-lg">Commencer gratuitement<span class="icon">arrow_forward</span></a>
                    <a href="signin.php" class="btn btn-ghost btn-lg">J'ai déjà un compte</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <div class="card mock">
                <div class="stat-label"><span class="icon">account_balance_wallet</span>Dépensé ce mois</div>
                <div class="stat-value">412,60 €</div>
                <div class="progress"><div class="progress-bar" style="width: 52%"></div></div>
                <div class="stat-sub">Il te reste 387,40 € sur 800 €</div>
                <ul class="tx-list" style="margin-top: 16px">
                    <li class="tx cat-Nourriture"><span class="tx-icon"><span class="icon">restaurant</span></span><div class="tx-main"><div class="tx-title">Courses</div><div class="tx-meta">Nourriture</div></div><span class="tx-amount">−42,30 €</span></li>
                    <li class="tx cat-Transport"><span class="tx-icon"><span class="icon">directions_bus</span></span><div class="tx-main"><div class="tx-title">Pass Navigo</div><div class="tx-meta">Transport</div></div><span class="tx-amount">−86,40 €</span></li>
                    <li class="tx cat-Loisirs"><span class="tx-icon"><span class="icon">sports_esports</span></span><div class="tx-main"><div class="tx-title">Cinéma</div><div class="tx-meta">Loisirs</div></div><span class="tx-amount">−12,00 €</span></li>
                </ul>
            </div>
            <img src="images/logo-icon.png" alt="" width="110" height="122">
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Pourquoi choisir Budgi ?</h2>
            <p>Simple, rapide et sans prise de tête : tout ce qu'il faut pour garder la main sur ton argent.</p>
        </div>
        <div class="features">
            <div class="card feature">
                <span class="icon">category</span>
                <h3>Dépenses par catégorie</h3>
                <p>Nourriture, loisirs, transport, études… Visualise en un coup d'œil où part ton argent.</p>
            </div>
            <div class="card feature">
                <span class="icon">notifications_active</span>
                <h3>Alerte dépassement</h3>
                <p>Fixe ta limite mensuelle : Budgi te prévient dès que tu t'en approches.</p>
            </div>
            <div class="card feature">
                <span class="icon">bolt</span>
                <h3>Ajout en 5 secondes</h3>
                <p>Un montant, une catégorie, c'est enregistré. Ton solde se met à jour instantanément.</p>
            </div>
        </div>
    </div>
</section>

<?php if (!$logged): ?>
<section class="section" style="padding-top: 0">
    <div class="container">
        <div class="cta-band">
            <h2>Prêt à reprendre le contrôle ?</h2>
            <p>Crée ton compte gratuitement en moins d'une minute.</p>
            <a href="register.php" class="btn btn-mint btn-lg">Créer mon compte</a>
        </div>
    </div>
</section>
<?php endif; ?>
<?php render_footer(); ?>
