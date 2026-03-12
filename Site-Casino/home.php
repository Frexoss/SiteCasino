<?php
session_start();
require_once 'config.php';
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
// Popup bonus bienvenue : on lit le flag puis on le supprime immédiatement
$showBonus = !empty($_SESSION['bonus_welcome']);
if ($showBonus) unset($_SESSION['bonus_welcome']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>God Bless Casino</title>
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<header class="banner">
  <img src="images/Logo.png" alt="Logo" class="logo">
  <nav><ul>
    <li><a href="home.php" class="active">Accueil</a></li>
    <li><a href="pages/jeux.php">Jeux</a></li>
    <li><a href="pages/payement.php">Paiement</a></li>
    <li><a href="pages/a-propos.php">À propos</a></li>
    <li><a href="pages/contact.php">Contact</a></li>
  </ul></nav>
  <div class="cta-buttons"><div class="user-menu">
    <?php if ($isAuth): ?>
      <a href="pages/payement.php" class="solde-container" style="text-decoration:none;">
        <span class="solde-label">Solde :</span>
        <span class="solde-montant"><?= htmlspecialchars($solde) ?> €</span>
      </a>
      <a href="pages/profil.php" class="btn btn-profil">
        <span class="profil-icon">👤</span>
        <span><?= htmlspecialchars($prenom) ?></span>
      </a>
      <a href="pages/logout.php" class="btn btn-logout">Déconnexion</a>
    <?php else: ?>
      <a href="pages/profil.php" class="btn btn-profil">
        <span class="profil-icon">👤</span>
        <span>Invité</span>
      </a>
      <a href="pages/login.php" class="btn btn-primary">Connexion / Inscription</a>
    <?php endif; ?>
  </div></div>
</header>

<section class="presentation">
  <h1>Bienvenue au God Bless Casino<?= $isAuth ? ', ' . htmlspecialchars($prenom) : '' ?> !</h1>
  <p>Découvrez l'expérience ultime du jeu en ligne ! Profitez d'une large sélection de jeux, de promotions exclusives et d'une sécurité optimale pour jouer en toute confiance.</p>
</section>

<section class="jeux-populaires">
  <h2>Nos jeux phares</h2>
  <div class="jeux-list">
    <div class="jeu"><img src="images/MachineASous.png" alt="Machines à sous"><h3>Machines à sous</h3><p>Des centaines de slots avec jackpots progressifs !</p></div>
    <div class="jeu"><img src="images/Roulette.png" alt="Roulette"><h3>Roulette</h3><p>Vivez l'ambiance du casino avec notre roulette en direct.</p></div>
    <div class="jeu"><img src="images/BlackJack.png" alt="Blackjack"><h3>Blackjack</h3><p>Affrontez le croupier et tentez de battre le 21 !</p></div>
    <div class="jeu"><img src="images/Poker.png" alt="Poker"><h3>Poker</h3><p>Rejoignez nos tables de poker et défiez d'autres joueurs.</p></div>
  </div>
  <a href="pages/jeux.php" class="btn btn-secondary">Voir tous les jeux</a>
</section>

<section class="promotions">
  <h2>Promotions &amp; Bonus</h2>
  <ul>
    <li>🎁 Bonus de bienvenue jusqu'à 500€</li>
    <li>🔄 Tours gratuits chaque semaine</li>
    <li>🏆 Tournois exclusifs avec gros gains</li>
  </ul>
  <a href="pages/payement.php" class="btn btn-primary">Profiter des offres</a>
</section>

<section class="securite">
  <h2>Sécurité &amp; Fiabilité</h2>
  <p>Votre sécurité est notre priorité : transactions cryptées, protection des données, jeu responsable et assistance 24/7.</p>
</section>

<section class="paiement">
  <h2>Moyens de paiement</h2>
  <p>Déposez et retirez vos gains facilement grâce à nos nombreux moyens de paiement sécurisés.</p>
  <a href="pages/payement.php" class="btn btn-secondary">En savoir plus</a>
</section>

<footer>
  <div class="footer-links">
    <a href="pages/a-propos.php">À propos</a> |
    <a href="pages/contact.php">Contact</a> |
    <a href="pages/profil.php">Mon compte</a>
  </div>
  <p>&copy; 2026 God Bless Casino. Tous droits réservés. | Jeu interdit aux mineurs | Jouez responsable</p>
</footer>
<?php if ($showBonus): ?>
<!-- ══ POPUP BONUS BIENVENUE ══ -->
<div id="bonusOverlay" style="
  position:fixed; inset:0; background:rgba(0,0,0,0.7);
  display:flex; align-items:center; justify-content:center;
  z-index:9999; animation: fadeInAbout 0.4s ease-in-out;">
  <div style="
    background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
    border: 2px solid #e94560;
    border-radius: 24px;
    box-shadow: 0 0 60px rgba(233,69,96,0.4);
    padding: 50px 44px 40px;
    max-width: 480px; width: 90%;
    text-align: center;
    animation: popupBounce 0.5s cubic-bezier(0.34,1.56,0.64,1);">
    <div style="font-size:3.5em; margin-bottom:14px;">🎉</div>
    <h2 style="color:#e94560; font-size:1.8em; margin:0 0 14px;">Bienvenue chez God Bless Casino !</h2>
    <p style="color:#e0e0e0; font-size:1.1em; line-height:1.6; margin-bottom:24px;">
      Bravo ! Vous avez obtenu un<br>
      <span style="color:#4caf50; font-size:1.5em; font-weight:700;">bonus de bienvenue de 500 €</span><br>
      directement crédité sur votre compte !
    </p>
    <div style="background:rgba(76,175,80,0.12); border:1px solid rgba(76,175,80,0.3); border-radius:12px; padding:14px; margin-bottom:28px;">
      <span style="color:#4caf50; font-size:1.3em; font-weight:700;">💰 + 500,00 €</span>
    </div>
    <button onclick="document.getElementById('bonusOverlay').style.display='none'"
      style="background:#e94560; color:#fff; border:none; border-radius:28px;
             padding:14px 50px; font-size:1.1em; font-weight:700; cursor:pointer;
             transition:background 0.2s; box-shadow:0 4px 20px rgba(233,69,96,0.4);"
      onmouseover="this.style.background='#c73652'"
      onmouseout="this.style.background='#e94560'">
      Commencer à jouer ! 🎰
    </button>
  </div>
</div>
<style>
@keyframes popupBounce {
  from { transform: scale(0.7); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}
</style>
<?php endif; ?>

</body>
</html>