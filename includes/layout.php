<?php
// Gabarit commun : <head>, barre de navigation et pied de page

// Catégories : icône Material Symbols + couleur (même couleur que .cat-* dans style.css)
const CATEGORIES = [
    'Nourriture' => ['restaurant', '#2bb8a3'],
    'Loisirs'    => ['sports_esports', '#8b5cf6'],
    'Transport'  => ['directions_bus', '#3b82f6'],
    'Santé'      => ['favorite', '#ef4444'],
    'Études'     => ['menu_book', '#f59e0b'],
    'Autres'     => ['more_horiz', '#64748b'],
];

const INCOME_CATEGORIES = [
    'Job'            => ['work', '#16a34a'],
    'Bourse'         => ['school', '#0ea5e9'],
    'Famille'        => ['family_restroom', '#ec4899'],
    'Aides (CAF…)'   => ['volunteer_activism', '#14b8a6'],
    'Autres revenus' => ['payments', '#65a30d'],
];

// Catégories valides pour un type de transaction ('expense' ou 'income')
function categories_for($type)
{
    return $type === 'income' ? INCOME_CATEGORIES : CATEGORIES;
}

// Effet d'une transaction sur le solde : un revenu l'augmente, une dépense le diminue
function balance_effect($type, $amount)
{
    return $type === 'income' ? (float) $amount : -(float) $amount;
}

// Message affiché une seule fois après une redirection
function flash($message = null, $type = 'success')
{
    if ($message !== null) {
        $_SESSION['flash'] = [$message, $type];
        return null;
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function euro($amount)
{
    return number_format((float) $amount, 2, ',', ' ') . ' €';
}

function render_header($title, $active = '', $logged = false, $extraHead = '')
{
    $links = $logged
        ? ['gestion' => ['gestion.php', 'Tableau de bord'], 'profile' => ['profile.php', 'Profil']]
        : ['home' => ['index.php', 'Accueil'], 'signin' => ['signin.php', 'Se connecter']];
    $home = $logged ? 'gestion.php' : 'index.php';
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> · Budgi</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,500,0,0&display=block">
    <link rel="stylesheet" href="style.css?v=12">
    <?= $extraHead ?>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a href="<?= $home ?>" class="brand" aria-label="Budgi, accueil">
                <img src="images/logo-horizontal.png" alt="Budgi" width="98" height="40">
            </a>
            <button type="button" class="nav-toggle" aria-label="Ouvrir le menu" aria-expanded="false">
                <span class="icon">menu</span>
            </button>
            <nav class="nav">
                <?php foreach ($links as $key => [$href, $label]): ?>
                    <a href="<?= $href ?>" class="nav-link<?= $active === $key ? ' is-active' : '' ?>"><?= $label ?></a>
                <?php endforeach; ?>
                <?php if ($logged): ?>
                    <a href="logout.php" class="btn btn-ghost btn-sm"><span class="icon">logout</span>Déconnexion</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-sm">Créer un compte</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>
<?php
}

function render_footer($extraScripts = '')
{
    ?>
    </main>
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Budgi · L'appli qui aide le portefeuille des étudiants</p>
    </footer>
    <script src="script.js?v=11" defer></script>
    <?= $extraScripts ?>
</body>
</html>
<?php
}

// Choix Dépense / Revenu + liste de catégories correspondante (basculée par script.js)
function render_type_picker($type = 'expense', $selected = '')
{
    ?>
    <div class="segmented" role="radiogroup" aria-label="Type de transaction">
        <label><input type="radio" name="type" value="expense"<?= $type !== 'income' ? ' checked' : '' ?>><span><span class="icon">south_east</span>Dépense</span></label>
        <label><input type="radio" name="type" value="income"<?= $type === 'income' ? ' checked' : '' ?>><span><span class="icon">north_east</span>Revenu</span></label>
    </div>
    <?php foreach (['expense', 'income'] as $t): $active = ($t === 'income') === ($type === 'income'); ?>
        <div class="field" data-type-field="<?= $t ?>"<?= $active ? '' : ' hidden' ?>>
            <label for="categorie-<?= $t ?>">Catégorie</label>
            <select id="categorie-<?= $t ?>" name="categorie" class="input"<?= $active ? '' : ' disabled' ?>>
                <?php foreach (categories_for($t) as $cat => $_): ?>
                    <option value="<?= e($cat) ?>"<?= $cat === $selected ? ' selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endforeach;
}

function render_alert($message, $type = 'error')
{
    if (empty($message)) {
        return;
    }
    $icon = $type === 'success' ? 'check_circle' : 'error';
    echo '<div class="alert alert-' . $type . '" role="alert"><span class="icon">' . $icon . '</span>' . e($message) . '</div>';
}
