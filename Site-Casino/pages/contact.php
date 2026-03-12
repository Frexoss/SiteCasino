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
    <li><a href="../pages/contact.php" class="active">Contact</a></li>
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

<main class="page-card">
  <h1>Contactez God Bless Casino 📧</h1>
  <p>Une question, une suggestion ou besoin d'aide ?<br>Notre équipe vous répond sous 24h !</p>
  <form id="contactForm" class="formulaire" autocomplete="off" style="max-width:500px;margin:0 auto;">
    <label for="objet">Objet de la demande :</label>
    <input type="text" id="objet" name="objet" required>
    <label for="emailc">Votre Email :</label>
    <input type="email" id="emailc" name="email" required>
    <label for="message">Message :</label>
    <textarea id="message" name="message" rows="5" required class="input-style"></textarea>
    <button type="submit" class="btn btn-primary">Envoyer</button>
  </form>
  <script>
    document.getElementById('contactForm').addEventListener('submit', function(e) {
      e.preventDefault();
      alert('Merci pour votre message ! Nous vous répondrons rapidement.');
      this.reset();
    });
  </script>
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