<?php
session_start();
require_once '../config.php';
$isAuth = isset($_SESSION['user_id']);
if ($isAuth) {
    $stmt = $pdo->prepare("SELECT solde FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row    = $stmt->fetch(PDO::FETCH_ASSOC);
    $solde  = number_format((float)$row['solde'], 2, ',', ' ');
    $prenom = $_SESSION['prenom'];
} else {
    $solde  = null;
    $prenom = 'Invité';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>God Bless Casino</title>
  <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
<header class="banner">
  <img src="../images/Logo.png" alt="Logo" class="logo">
  <nav><ul>
    <li><a href="../home.php">Accueil</a></li>
    <li><a href="../pages/jeux.php">Jeux</a></li>
    <li><a href="../pages/payement.php">Paiement</a></li>
    <li><a href="../pages/a-propos.php">À propos</a></li>
    <li><a href="../pages/contact.php">Contact</a></li>
  </ul></nav>
  <div class="cta-buttons"><div class="user-menu">
    <?php if ($isAuth): ?>
      <a href="../pages/payement.php" class="solde-container" style="text-decoration:none;">
        <span class="solde-label">Solde :</span>
        <span class="solde-montant"><?= htmlspecialchars($solde) ?> €</span>
      </a>
      <a href="../pages/profil.php" class="btn btn-profil">
        <span class="profil-icon">👤</span>
        <span><?= htmlspecialchars($prenom) ?></span>
      </a>
      <a href="../pages/logout.php" class="btn btn-logout">Déconnexion</a>
    <?php else: ?>
      <a href="../pages/profil.php" class="btn btn-profil">
        <span class="profil-icon">👤</span>
        <span>Invité</span>
      </a>
      <a href="../pages/login.php" class="btn btn-primary">Connexion / Inscription</a>
    <?php endif; ?>
  </div></div>
</header>

<div class="profil-wrap">
  <div class="profil-avatar">👤</div>
  <h1>Mon Profil</h1>

  <?php if (!$isAuth): ?>
  <div class="guest-banner">
    Vous naviguez en mode invité. <a href="login.php">Connectez-vous</a> pour accéder à votre vrai profil.
  </div>
  <?php endif; ?>

  <?php
  if ($isAuth) {
      require_once '../config.php';
      $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
      $stmt->execute([$_SESSION['user_id']]);
      $u     = $stmt->fetch();
      $age   = (int)date_diff(date_create($u['date_naissance']), date_create('today'))->y;
      $solde = number_format((float)$u['solde'], 2, ',', ' ');
      $ok    = '';
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          $nom     = trim($_POST['nom'] ?? '');
          $prenom  = trim($_POST['prenom'] ?? '');
          $adresse = trim($_POST['adresse'] ?? '');
          $pdo->prepare("UPDATE utilisateurs SET nom=?,prenom=?,adresse=? WHERE id=?")
              ->execute([$nom, $prenom, $adresse, $_SESSION['user_id']]);
          $_SESSION['prenom'] = $prenom;
          $_SESSION['nom']    = $nom;
          $ok = 'Profil mis à jour !';
          // Refresh
          $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
          $stmt->execute([$_SESSION['user_id']]);
          $u = $stmt->fetch();
      }
  } else {
      $u     = ['nom'=>'Compte invité','prenom'=>'Compte invité','email'=>'Compte invité','adresse'=>'Compte invité'];
      $age   = 67;
      $solde = '67,00';
      $ok    = '';
  }
  ?>

  <div class="profil-infos">
    <div class="profil-info-row"><span class="profil-info-label">Nom</span><span class="profil-info-value"><?= htmlspecialchars($u['nom']) ?></span></div>
    <div class="profil-info-row"><span class="profil-info-label">Prénom</span><span class="profil-info-value"><?= htmlspecialchars($u['prenom']) ?></span></div>
    <div class="profil-info-row"><span class="profil-info-label">Âge</span><span class="profil-info-value"><?= $age ?> ans</span></div>
    <div class="profil-info-row"><span class="profil-info-label">Adresse e-mail</span><span class="profil-info-value"><?= htmlspecialchars($u['email']) ?></span></div>
    <div class="profil-info-row"><span class="profil-info-label">Adresse</span><span class="profil-info-value"><?= htmlspecialchars($u['adresse']) ?></span></div>
    <div class="profil-info-row"><span class="profil-info-label">Solde</span><span class="profil-info-value profil-solde-value"><?= htmlspecialchars($solde) ?> €</span></div>
  </div>

  <?php if ($isAuth): ?>
  <div class="profil-edit">
    <h2>✏️ Modifier mes informations</h2>
    <?php if ($ok): ?><div class="success-msg"><?= $ok ?></div><?php endif; ?>
    <form method="POST">
      <label>Nom</label><input type="text" name="nom" value="<?= htmlspecialchars($u['nom']) ?>" required>
      <label>Prénom</label><input type="text" name="prenom" value="<?= htmlspecialchars($u['prenom']) ?>" required>
      <label>Adresse</label><input type="text" name="adresse" value="<?= htmlspecialchars($u['adresse']) ?>" required>
      <button type="submit" class="btn btn-primary" style="width:100%;">Enregistrer</button>
    </form>
  </div>
  <?php else: ?>
  <div style="text-align:center;margin-top:16px;">
    <a href="login.php" class="btn btn-primary btn-lg">Créer un compte</a>
  </div>
  <?php endif; ?>
</div>

<footer>
  <div class="footer-links">
    <a href="../pages/a-propos.php">À propos</a> |
    <a href="../pages/contact.php">Contact</a> |
    <a href="../pages/profil.php">Mon compte</a>
  </div>
  <p>&copy; 2026 God Bless Casino. Tous droits réservés. | Jeu interdit aux mineurs | Jouez responsable</p>
</footer>
</body>
</html>