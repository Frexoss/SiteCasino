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
    <li><a href="../pages/a-propos.php" class="active">À propos</a></li>
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

<main class="page-card">
  <h1>À propos de God Bless Casino 🎰</h1>
  <p>God Bless Casino est un casino en ligne tout récent, créé avec l'ambition de se faire une place parmi les plateformes de jeu les plus appréciées. Notre projet est né d'une passion pour l'univers du casino et d'une envie simple : proposer une expérience moderne, fluide et divertissante pour tous les joueurs.</p>
  <p>En tant que nouveau casino en ligne, notre objectif est clair : gagner en visibilité, bâtir une communauté de joueurs fidèles et offrir un environnement de jeu agréable, intuitif et immersif. Chaque détail du site a été pensé pour rappeler l'ambiance des vrais casinos, tout en restant accessible depuis chez soi.</p>
  <p>Sur God Bless Casino, tu retrouveras les grands classiques du jeu en ligne 🎲 : machines à sous, roulette, blackjack et poker. Ces jeux ont été sélectionnés pour leur popularité, leur simplicité de prise en main et le plaisir qu'ils procurent, aussi bien aux débutants qu'aux joueurs plus expérimentés.</p>
  <p>Notre casino met également un point d'honneur à proposer une navigation claire et des fonctionnalités simples. Que ce soit pour jouer, consulter ton profil ou gérer ton solde, tout est fait pour que ton expérience soit rapide et sans prise de tête 💻.</p>
  <p>La sécurité et le jeu responsable font aussi partie de nos priorités 🔒. God Bless Casino se veut avant tout un espace de divertissement. Nous encourageons chaque joueur à jouer avec modération et à considérer le casino comme un loisir, et non comme un moyen de gagner de l'argent.</p>
  <p>En rejoignant God Bless Casino, tu fais partie des premiers joueurs de cette nouvelle aventure. Notre ambition est de continuer à évoluer, à améliorer la plateforme et à proposer toujours plus de contenu à l'avenir 🚀.</p>
  <p>Merci de faire confiance à God Bless Casino et bon jeu !</p>
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