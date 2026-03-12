<?php
session_start();
// Si déjà connecté, retour accueil
if (isset($_SESSION['user_id'])) {
    header('Location: ../home.php');
    exit;
}
require_once '../config.php';

$errorLogin = '';
$errorReg   = '';

// ── FAUX UTILISATEUR DE TEST (à supprimer quand la BDD est prête) ──
$fakeUsers = [
    'test@test.com' => [
        'id'     => 1,
        'prenom' => 'Jean',
        'nom'    => 'Dupont',
        'email'  => 'test@test.com',
        'mdp'    => 'test123',
    ],
    'admin@casino.com' => [
        'id'     => 2,
        'prenom' => 'Admin',
        'nom'    => 'Casino',
        'email'  => 'admin@casino.com',
        'mdp'    => 'admin123',
    ],
];

// ── CONNEXION ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['motdepasse'] ?? '';

    // Vérifier d'abord les faux utilisateurs
    if (isset($fakeUsers[$email]) && $fakeUsers[$email]['mdp'] === $mdp) {
        $u = $fakeUsers[$email];
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['prenom']  = $u['prenom'];
        $_SESSION['nom']     = $u['nom'];
        $_SESSION['email']   = $u['email'];
        header('Location: ../home.php');
        exit;
    }

    // Sinon essayer la BDD
    $stmt  = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user  = $stmt->fetch();
    if ($user && $mdp === $user['motdepasse']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['prenom']  = $user['prenom'];
        $_SESSION['nom']     = $user['nom'];
        $_SESSION['email']   = $user['email'];
        header('Location: ../home.php');
        exit;
    } else {
        $errorLogin = 'Email ou mot de passe incorrect.';
    }
}

// ── INSCRIPTION ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $email   = trim($_POST['email'] ?? '');
    $nom     = trim($_POST['nom'] ?? '');
    $prenom  = trim($_POST['prenom'] ?? '');
    $dob     = $_POST['date_naissance'] ?? '';
    $adresse = trim($_POST['adresse'] ?? '');
    $mdp     = $_POST['motdepasse'] ?? '';
    $age     = (int)date_diff(date_create($dob), date_create('today'))->y;
    if ($age < 18) {
        $errorReg = 'Vous devez avoir au moins 18 ans.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errorReg = 'Cet email est déjà utilisé.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (email,nom,prenom,date_naissance,adresse,motdepasse,solde) VALUES (?,?,?,?,?,?,500)");
            $stmt->execute([$email, $nom, $prenom, $dob, $adresse, $mdp]);
            $_SESSION['user_id']      = $pdo->lastInsertId();
            $_SESSION['prenom']       = $prenom;
            $_SESSION['nom']          = $nom;
            $_SESSION['email']        = $email;
            $_SESSION['bonus_welcome'] = true; // afficher le popup sur home.php
            header('Location: ../home.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion / Inscription - God Bless Casino</title>
  <link rel="stylesheet" href="../styles/style.css">
</head>
<body class="login-page">
  <img src="../images/Logo.png" alt="Logo" class="login-logo">
  <h1 class="login-title">God Bless Casino 🎰</h1>
  <p class="login-subtitle">Connectez-vous ou créez un compte</p>

  <div class="login-container">

    <!-- CONNEXION -->
    <div class="login-card">
      <h2>🔑 Connexion</h2>
      <?php if ($errorLogin): ?><div class="err"><?= htmlspecialchars($errorLogin) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="login">
        <label>Adresse e-mail</label>
        <input type="email" name="email" required placeholder="votre@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <label>Mot de passe</label>
        <input type="password" name="motdepasse" required placeholder="••••••••">
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:6px;">Se connecter</button>
      </form>
    </div>

    <div class="divider"></div>

    <!-- INSCRIPTION -->
    <div class="login-card">
      <h2>✨ Inscription</h2>
      <?php if ($errorReg): ?><div class="err"><?= htmlspecialchars($errorReg) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="register">
        <label>Adresse e-mail</label>
        <input type="email" name="email" required placeholder="votre@email.com">
        <label>Nom</label>
        <input type="text" name="nom" required placeholder="Dupont">
        <label>Prénom</label>
        <input type="text" name="prenom" required placeholder="Jean">
        <label>Date de naissance</label>
        <input type="date" name="date_naissance" required>
        <label>Adresse</label>
        <input type="text" name="adresse" required placeholder="123 rue de la Chance">
        <label>Mot de passe</label>
        <input type="password" name="motdepasse" required placeholder="••••••••">
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:6px;">S'inscrire</button>
      </form>
    </div>

  </div>

  <a href="../home.php" class="back-link">← Retour au site</a>
</body>
</html>
