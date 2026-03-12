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
    <li><a href="../pages/payement.php" class="active">Paiement</a></li>
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

<?php
$successPay = '';
$errorPay   = '';
if ($isAuth && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config.php';
    $montant  = (float)($_POST['montant'] ?? 0);
    $paiement = $_POST['paiement'] ?? '';
    if ($montant < 10) {
        $errorPay = 'Le montant minimum est de 10 €.';
    } elseif (empty($paiement)) {
        $errorPay = 'Veuillez choisir un moyen de paiement.';
    } else {
        $pdo->prepare("UPDATE utilisateurs SET solde = solde + ? WHERE id = ?")
            ->execute([$montant, $_SESSION['user_id']]);
        $pdo->prepare("INSERT INTO transactions (user_id,type,montant,moyen_paiement) VALUES (?,?,?,?)")
            ->execute([$_SESSION['user_id'], 'depot', $montant, $paiement]);
        // Refresh solde
        $stmt = $pdo->prepare("SELECT solde FROM utilisateurs WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row   = $stmt->fetch();
        $solde = number_format((float)$row['solde'], 2, ',', ' ');
        $successPay = number_format($montant, 2, ',', ' ') . ' € ajoutés à votre solde !';
    }
}
?>

<main class="page-card">
  <h1>Recharger mon solde 💵</h1>

  <?php if (!$isAuth): ?>
    <div style="text-align:center;padding:50px 20px;">
      <p style="font-size:1.2em;color:#b0b0b0;margin-bottom:24px;">Connectez-vous pour recharger votre solde.</p>
      <a href="login.php" class="btn btn-primary btn-lg">Connexion / Inscription</a>
    </div>
  <?php else: ?>
    <?php if ($successPay): ?>
      <div style="color:#4caf50;background:rgba(76,175,80,0.1);border-radius:8px;padding:12px;max-width:420px;margin:0 auto 16px;text-align:center;"><?= $successPay ?></div>
    <?php endif; ?>
    <?php if ($errorPay): ?>
      <div style="color:#e94560;background:rgba(233,69,96,0.1);border-radius:8px;padding:12px;max-width:420px;margin:0 auto 16px;text-align:center;"><?= $errorPay ?></div>
    <?php endif; ?>
    <form class="formulaire" method="POST" style="max-width:420px;margin:30px auto 0;text-align:left;">
      <label for="montant">Montant à déposer (min 10 €) :</label>
      <input type="number" id="montant" name="montant" min="10" value="10" required style="width:100%;padding:9px;margin:8px 0 18px;">
      <label>Moyen de paiement :</label>
      <div class="moyens moyens-vertical">
        <label><input type="radio" name="paiement" value="carte" required> Carte bancaire</label>
        <label><input type="radio" name="paiement" value="paypal"> PayPal</label>
        <label><input type="radio" name="paiement" value="crypto"> Crypto-monnaie</label>
        <label><input type="radio" name="paiement" value="applepay"> Apple Pay</label>
        <label><input type="radio" name="paiement" value="googlepay"> Google Pay</label>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">Payer</button>
    </form>
  <?php endif; ?>
</main>

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