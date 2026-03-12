<?php
session_start();
require_once '../config.php';
$isAuth = isset($_SESSION['user_id']);

if ($isAuth) {
    $stmt = $pdo->prepare("SELECT solde FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row    = $stmt->fetch(PDO::FETCH_ASSOC);
    $solde  = (float)$row['solde'];
    $prenom = $_SESSION['prenom'];
} else {
    $solde  = 0;
    $prenom = 'Invité';
}

// ══════════════════════════════════════════
// AJAX — MACHINE À SOUS
// ══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'slots') {
    header('Content-Type: application/json');
    if (!$isAuth) { echo json_encode(['error' => 'Connectez-vous pour jouer.']); exit; }

    $mise = (float)($_POST['mise'] ?? 0);
    if ($mise <= 0)       { echo json_encode(['error' => 'Mise invalide.']); exit; }
    if ($mise > $solde)   { echo json_encode(['error' => 'Solde insuffisant.']); exit; }

    $emojis = ['🍒','🍋','🍊','🍇','⭐','💎','7️⃣','🔔'];
    $r1 = $emojis[array_rand($emojis)];
    $r2 = $emojis[array_rand($emojis)];
    $r3 = $emojis[array_rand($emojis)];

    if ($r1 === $r2 && $r2 === $r3) {
        // 3 identiques : mise x4 (on a deja deduit la mise, donc on rend 4x)
        $delta = $mise * 3; $type = 'jackpot'; $msg = 'JACKPOT ! 3 identiques ! Mise × 4 ! 🎉🎉';
    } elseif ($r1===$r2 || $r2===$r3 || $r1===$r3) {
        // 2 identiques : mise x2 (on a deja deduit la mise, donc on rend 2x)
        $delta = $mise; $type = 'rembourse'; $msg = '2 identiques ! Mise doublée ! 💰';
    } else {
        // 0 identique : mise perdue
        $delta = -$mise; $type = 'perdu'; $msg = 'Pas de chance... Aucune combinaison. 😞';
    }

    $nouveauSolde = max(0, $solde + $delta);
    $pdo->prepare("UPDATE utilisateurs SET solde=? WHERE id=?")->execute([$nouveauSolde, $_SESSION['user_id']]);
    echo json_encode(['r1'=>$r1,'r2'=>$r2,'r3'=>$r3,'type'=>$type,'message'=>$msg,'nouveauSolde'=>number_format($nouveauSolde,2,',',' '),'soldeRaw'=>$nouveauSolde]);
    exit;
}


// ══════════════════════════════════════════
// AJAX — BLACKJACK
// ══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bj') {
    header('Content-Type: application/json');
    if (!$isAuth) { echo json_encode(['error' => 'Connectez-vous pour jouer.']); exit; }

    $acte = $_POST['acte'] ?? ''; // 'deal', 'hit', 'stand'
    $mise = (float)($_POST['mise'] ?? 0);

    // Valeur d'une carte
    function cardValue($card) {
        $val = $card['val'];
        if (in_array($val, ['J','Q','K'])) return 10;
        if ($val === 'A') return 11;
        return (int)$val;
    }

    // Total d'une main (gestion As à 1 ou 11)
    function handTotal($hand) {
        $total = 0; $aces = 0;
        foreach ($hand as $c) {
            $v = cardValue($c);
            if ($c['val'] === 'A') $aces++;
            $total += $v;
        }
        while ($total > 21 && $aces > 0) { $total -= 10; $aces--; }
        return $total;
    }

    // Créer un paquet mélangé
    function newDeck() {
        $vals  = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];
        $suits = ['♠','♥','♦','♣'];
        $deck  = [];
        foreach ($suits as $s) foreach ($vals as $v) $deck[] = ['val'=>$v,'suit'=>$s];
        shuffle($deck);
        return $deck;
    }

    // Représentation texte d'une carte
    function cardStr($c) { return $c['val'].$c['suit']; }

    if ($acte === 'deal') {
        if ($mise <= 0)     { echo json_encode(['error'=>'Mise invalide.']); exit; }
        if ($mise > $solde) { echo json_encode(['error'=>'Solde insuffisant.']); exit; }

        $deck   = newDeck();
        $player = [array_pop($deck), array_pop($deck)];
        $dealer = [array_pop($deck), array_pop($deck)];

        // Deduire la mise tout de suite
        $soldeApresMise = $solde - $mise;
        $pdo->prepare("UPDATE utilisateurs SET solde=? WHERE id=?")->execute([$soldeApresMise, $_SESSION['user_id']]);

        $pTotal = handTotal($player);
        $dTotal = handTotal($dealer);

        // Blackjack immediat : resoudre cote serveur directement
        if ($pTotal === 21 || $dTotal === 21) {
            if ($pTotal === 21 && $dTotal === 21) {
                $resultat = 'draw'; $msg = 'Double Blackjack ! Egalite. Rembourse.'; $delta = $mise;
            } elseif ($pTotal === 21) {
                $resultat = 'win'; $msg = 'BLACKJACK ! Vous gagnez ! 🎉'; $delta = $mise * 2;
            } else {
                $resultat = 'lose'; $msg = 'Blackjack du croupier. Vous perdez.'; $delta = 0;
            }
            $nouveauSolde = max(0, $soldeApresMise + $delta);
            $pdo->prepare("UPDATE utilisateurs SET solde=? WHERE id=?")->execute([$nouveauSolde, $_SESSION['user_id']]);
            echo json_encode([
                'player'       => array_map('cardStr', $player),
                'dealer'       => array_map('cardStr', $dealer),
                'pTotal'       => $pTotal,
                'dTotal'       => $dTotal,
                'blackjack'    => true,
                'resultat'     => $resultat,
                'message'      => $msg,
                'nouveauSolde' => number_format($nouveauSolde, 2, ',', ' '),
                'soldeRaw'     => $nouveauSolde,
            ]);
            exit;
        }

        // Partie normale : stocker en session
        $_SESSION['bj'] = ['deck'=>$deck,'player'=>$player,'dealer'=>$dealer,'mise'=>$mise,'soldeApresMise'=>$soldeApresMise];

        echo json_encode([
            'player'       => array_map('cardStr', $player),
            'dealer'       => [cardStr($dealer[0]), 'back'],
            'pTotal'       => $pTotal,
            'blackjack'    => false,
            'nouveauSolde' => number_format($soldeApresMise, 2, ',', ' '),
            'soldeRaw'     => $soldeApresMise,
        ]);
        exit;
    }

    if ($acte === 'hit') {
        if (!isset($_SESSION['bj'])) { echo json_encode(['error'=>'Aucune partie en cours.']); exit; }
        $bj     = $_SESSION['bj'];
        $card   = array_pop($bj['deck']);
        $bj['player'][] = $card;
        $pTotal = handTotal($bj['player']);
        $bust   = $pTotal > 21;

        if ($bust) {
            // Bust : on resout tout ici, solde deja deduit, on ne rend rien
            $soldeApresMise = $bj['soldeApresMise'];
            unset($_SESSION['bj']);
            echo json_encode([
                'newCard'      => cardStr($card),
                'player'       => array_map('cardStr', $bj['player']),
                'pTotal'       => $pTotal,
                'bust'         => true,
                'nouveauSolde' => number_format($soldeApresMise, 2, ',', ' '),
                'soldeRaw'     => $soldeApresMise,
            ]);
        } else {
            $_SESSION['bj'] = $bj;
            echo json_encode([
                'newCard' => cardStr($card),
                'player'  => array_map('cardStr', $bj['player']),
                'pTotal'  => $pTotal,
                'bust'    => false,
            ]);
        }
        exit;
    }

    if ($acte === 'stand') {
        if (!isset($_SESSION['bj'])) { echo json_encode(['error'=>'Aucune partie en cours.']); exit; }
        $bj             = $_SESSION['bj'];
        $mise           = $bj['mise'];
        $soldeApresMise = $bj['soldeApresMise'];

        // Croupier tire jusqu'a 17
        while (handTotal($bj['dealer']) < 17) {
            $bj['dealer'][] = array_pop($bj['deck']);
        }
        $pTotal = handTotal($bj['player']);
        $dTotal = handTotal($bj['dealer']);

        if ($dTotal > 21 || $pTotal > $dTotal) {
            $resultat = 'win';  $msg = 'Vous gagnez ! 🎉'; $delta = $mise * 2;
        } elseif ($pTotal === $dTotal) {
            $resultat = 'draw'; $msg = 'Egalite ! Rembourse. 🤝'; $delta = $mise;
        } else {
            $resultat = 'lose'; $msg = 'Le croupier gagne. 😞'; $delta = 0;
        }

        $nouveauSolde = max(0, $soldeApresMise + $delta);
        $pdo->prepare("UPDATE utilisateurs SET solde=? WHERE id=?")->execute([$nouveauSolde, $_SESSION['user_id']]);
        unset($_SESSION['bj']);

        echo json_encode([
            'dealer'       => array_map('cardStr', $bj['dealer']),
            'dTotal'       => $dTotal,
            'pTotal'       => $pTotal,
            'resultat'     => $resultat,
            'message'      => $msg,
            'nouveauSolde' => number_format($nouveauSolde, 2, ',', ' '),
            'soldeRaw'     => $nouveauSolde,
        ]);
        exit;
    }
}

$soldeFormate = number_format($solde, 2, ',', ' ');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jeux - God Bless Casino</title>
  <link rel="stylesheet" href="../styles/style.css">
</head>
<body>

<header class="banner">
  <img src="../images/Logo.png" alt="Logo" class="logo">
  <nav><ul>
    <li><a href="../home.php">Accueil</a></li>
    <li><a href="jeux.php" class="active">Jeux</a></li>
    <li><a href="payement.php">Paiement</a></li>
    <li><a href="a-propos.php">À propos</a></li>
    <li><a href="contact.php">Contact</a></li>
  </ul></nav>
  <div class="cta-buttons"><div class="user-menu">
    <?php if ($isAuth): ?>
      <a href="payement.php" class="solde-container" style="text-decoration:none;">
        <span class="solde-label">Solde :</span>
        <span class="solde-montant" id="headerSolde"><?= htmlspecialchars($soldeFormate) ?> €</span>
      </a>
      <a href="profil.php" class="btn btn-profil">
        <span class="profil-icon">👤</span>
        <span><?= htmlspecialchars($prenom) ?></span>
      </a>
      <a href="logout.php" class="btn btn-logout">Déconnexion</a>
    <?php else: ?>
      <a href="profil.php" class="btn btn-profil"><span class="profil-icon">👤</span><span>Invité</span></a>
      <a href="login.php" class="btn btn-primary">Connexion / Inscription</a>
    <?php endif; ?>
  </div></div>
</header>

<div class="jeux-container">

  <!-- ══════════════════ MACHINE À SOUS ══════════════════ -->
  <div class="game-card">
    <h2>🎰 Machine à Sous</h2>
    <p class="sous-titre">Faites tourner les rouleaux et tentez le jackpot !</p>

    <?php if (!$isAuth): ?>
      <div class="guest-msg"><p style="margin:0 0 14px;">Connectez-vous pour jouer.</p><a href="login.php" class="btn btn-primary">Se connecter</a></div>
    <?php else: ?>
      <div class="machine">
        <div class="reel" id="s1">🎰</div>
        <div class="reel" id="s2">🎰</div>
        <div class="reel" id="s3">🎰</div>
      </div>
      <div class="mise-row">
        <label>Mise :</label>
        <input type="number" id="miseSlots" class="mise-input" min="1" value="10" step="1">
        <span style="color:#b0b0b0;">€</span>
      </div>
      <button class="btn-jouer" id="btnSlots" onclick="jouerSlots()">🎲 Lancer !</button>
      <div class="result-box" id="resultSlots"></div>
      <div class="solde-jeu">Solde : <span id="soldeSlots"><?= htmlspecialchars($soldeFormate) ?></span> €</div>
      <div class="regles">
        <h3>📋 Règles</h3>
        <ul>
          <li>❌ Aucun identique → mise perdue</li>
          <li>💰 2 identiques → mise × 2 !</li>
          <li>🏆 3 identiques → mise × 4 !</li>
        </ul>
      </div>
    <?php endif; ?>
  </div>

  <!-- ══════════════════ BLACKJACK ══════════════════ -->
  <div class="game-card">
    <h2>🃏 Blackjack</h2>
    <p class="sous-titre">Battez le croupier sans dépasser 21 !</p>

    <?php if (!$isAuth): ?>
      <div class="guest-msg"><p style="margin:0 0 14px;">Connectez-vous pour jouer.</p><a href="login.php" class="btn btn-primary">Se connecter</a></div>
    <?php else: ?>
      <!-- Mise + Démarrer -->
      <div id="bjStart">
        <div class="mise-row">
          <label>Mise :</label>
          <input type="number" id="miseBJ" class="mise-input" min="1" value="10" step="1">
          <span style="color:#b0b0b0;">€</span>
        </div>
        <button class="btn-jouer" id="btnDeal" onclick="bjDeal()">🃏 Distribuer !</button>
      </div>

      <!-- Table de jeu -->
      <div class="bj-table" id="bjTable" style="display:none;">
        <div class="bj-label">Croupier</div>
        <div class="bj-cards" id="dealerCards"></div>
        <div class="bj-total">Total croupier : <span id="dealerTotal">?</span></div>

        <div class="bj-label">Vous</div>
        <div class="bj-cards" id="playerCards"></div>
        <div class="bj-total">Votre total : <span id="playerTotal">0</span></div>

        <div class="bj-actions">
          <button class="btn-bj btn-tirer"  id="btnHit"   onclick="bjHit()">➕ Tirer</button>
          <button class="btn-bj btn-rester" id="btnStand" onclick="bjStand()">✋ Rester</button>
        </div>
      </div>

      <div class="result-box" id="resultBJ"></div>

      <!-- Bouton nouvelle partie (caché au début) -->
      <button class="btn-jouer" id="btnNewBJ" onclick="bjReset()" style="display:none;margin-top:14px;background:#0f3460;">🔄 Nouvelle partie</button>

      <div class="solde-jeu">Solde : <span id="soldeBJ"><?= htmlspecialchars($soldeFormate) ?></span> €</div>

      <div class="regles">
        <h3>📋 Règles</h3>
        <ul>
          <li>Figures (J/Q/K) = 10, As = 1 ou 11</li>
          <li>Dépassez 21 → vous perdez (bust)</li>
          <li>Le croupier tire jusqu'à 17 minimum</li>
          <li>Victoire → mise × 2 | Égalité → remboursé</li>
        </ul>
      </div>
    <?php endif; ?>
  </div>

</div><!-- .jeux-container -->


<footer>
  <div class="footer-links">
    <a href="a-propos.php">À propos</a> |
    <a href="contact.php">Contact</a> |
    <a href="profil.php">Mon compte</a>
  </div>
  <p>&copy; 2026 God Bless Casino. Tous droits réservés. | Jeu interdit aux mineurs | Jouez responsable</p>
</footer>

<script>
let soldeRaw = <?= $solde ?>;

function updateSolde(valFormate, valRaw) {
  soldeRaw = valRaw;
  ['soldeSlots','soldeBJ'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = valFormate;
  });
  const h = document.getElementById('headerSolde');
  if (h) h.textContent = valFormate + ' €';
  ['miseSlots','miseBJ'].forEach(id => {
    const inp = document.getElementById(id);
    if (inp) inp.max = Math.floor(valRaw);
  });
}

function showResult(id, msg, type) {
  const box = document.getElementById(id);
  box.className = 'result-box ' + type;
  box.textContent = msg;
  box.style.display = 'block';
}

// ══════════════════════════════════════════
// MACHINE À SOUS
// ══════════════════════════════════════════
async function jouerSlots() {
  const btn  = document.getElementById('btnSlots');
  const mise = parseFloat(document.getElementById('miseSlots').value);
  if (!mise || mise <= 0) { showResult('resultSlots','Mise invalide.','erreur'); return; }
  if (mise > soldeRaw)    { showResult('resultSlots','Solde insuffisant.','erreur'); return; }

  btn.disabled = true;
  document.getElementById('resultSlots').style.display = 'none';
  ['s1','s2','s3'].forEach(id => { const r = document.getElementById(id); r.classList.add('spin'); r.textContent='❓'; });

  const form = new FormData();
  form.append('action','slots');
  form.append('mise', mise);
  const res  = await fetch('jeux.php', {method:'POST', body:form});
  const data = await res.json();

  setTimeout(() => {
    ['s1','s2','s3'].forEach(id => document.getElementById(id).classList.remove('spin'));
    if (data.error) {
      ['s1','s2','s3'].forEach(id => document.getElementById(id).textContent='🚫');
      showResult('resultSlots', data.error, 'erreur');
    } else {
      document.getElementById('s1').textContent = data.r1;
      document.getElementById('s2').textContent = data.r2;
      document.getElementById('s3').textContent = data.r3;
      showResult('resultSlots', data.message, data.type);
      updateSolde(data.nouveauSolde, data.soldeRaw);
    }
    btn.disabled = false;
  }, 700);
}

// ══════════════════════════════════════════
// BLACKJACK
// ══════════════════════════════════════════
function makeCard(str) {
  const suit = str.slice(-1);
  const val  = str.slice(0,-1);
  const isRed = suit === '♥' || suit === '♦';
  const div = document.createElement('div');
  div.className = 'card new' + (isRed ? ' red' : '');
  div.textContent = str;
  return div;
}

function setCards(containerId, cards, hideSecond) {
  const c = document.getElementById(containerId);
  c.innerHTML = '';
  cards.forEach((card, i) => {
    if (hideSecond && i === 1 || card === 'back') {
      const div = document.createElement('div');
      div.className = 'card back'; div.textContent = '🂠';
      c.appendChild(div);
    } else {
      c.appendChild(makeCard(card));
    }
  });
}

async function bjCall(body) {
  const form = new FormData();
  for (const [k,v] of Object.entries(body)) form.append(k,v);
  form.append('action','bj');
  const res = await fetch('jeux.php', {method:'POST', body:form});
  return await res.json();
}

async function bjDeal() {
  const mise = parseFloat(document.getElementById('miseBJ').value);
  if (!mise || mise <= 0) { showResult('resultBJ','Mise invalide.','erreur'); return; }
  if (mise > soldeRaw)    { showResult('resultBJ','Solde insuffisant.','erreur'); return; }

  document.getElementById('btnDeal').disabled = true;
  document.getElementById('resultBJ').style.display = 'none';

  const data = await bjCall({acte:'deal', mise});
  if (data.error) { showResult('resultBJ', data.error, 'erreur'); document.getElementById('btnDeal').disabled=false; return; }

  document.getElementById('bjStart').style.display = 'none';
  document.getElementById('bjTable').style.display  = 'block';
  updateSolde(data.nouveauSolde, data.soldeRaw);

  // Blackjack immediat : tout est deja resolu cote serveur
  if (data.blackjack) {
    setCards('dealerCards', data.dealer, false);
    setCards('playerCards', data.player, false);
    document.getElementById('playerTotal').textContent = data.pTotal;
    document.getElementById('dealerTotal').textContent = data.dTotal;
    showResult('resultBJ', data.message, data.resultat);
    endBJ();
    return;
  }

  // Partie normale
  setCards('dealerCards', data.dealer, true);
  setCards('playerCards', data.player, false);
  document.getElementById('playerTotal').textContent = data.pTotal;
  document.getElementById('dealerTotal').textContent = '?';
}

async function bjHit() {
  document.getElementById('btnHit').disabled  = true;
  document.getElementById('btnStand').disabled = true;

  const data = await bjCall({acte:'hit'});
  if (data.error) { showResult('resultBJ', data.error,'erreur'); endBJ(); return; }

  const c = document.getElementById('playerCards');
  c.appendChild(makeCard(data.newCard));
  document.getElementById('playerTotal').textContent = data.pTotal;

  if (data.bust) {
    showResult('resultBJ', 'Bust ! Vous dépassez 21. 💥', 'lose');
    updateSolde(data.nouveauSolde, data.soldeRaw);
    endBJ();
  } else {
    document.getElementById('btnHit').disabled  = false;
    document.getElementById('btnStand').disabled = false;
  }
}

async function bjStand() {
  document.getElementById('btnHit').disabled  = true;
  document.getElementById('btnStand').disabled = true;

  const data = await bjCall({acte:'stand'});
  if (data.error) { showResult('resultBJ', data.error,'erreur'); return; }

  setCards('dealerCards', data.dealer, false);
  document.getElementById('dealerTotal').textContent = data.dTotal;
  document.getElementById('playerTotal').textContent = data.pTotal;
  showResult('resultBJ', data.message, data.resultat);
  updateSolde(data.nouveauSolde, data.soldeRaw);
  endBJ();
}

function endBJ() {
  document.getElementById('btnHit').disabled   = true;
  document.getElementById('btnStand').disabled  = true;
  document.getElementById('btnNewBJ').style.display = 'inline-block';
}

function bjReset() {
  document.getElementById('bjTable').style.display   = 'none';
  document.getElementById('bjStart').style.display   = 'block';
  document.getElementById('btnDeal').disabled        = false;
  document.getElementById('btnNewBJ').style.display  = 'none';
  document.getElementById('resultBJ').style.display  = 'none';
  document.getElementById('dealerCards').innerHTML   = '';
  document.getElementById('playerCards').innerHTML   = '';
  document.getElementById('dealerTotal').textContent = '?';
  document.getElementById('playerTotal').textContent = '0';
  document.getElementById('btnHit').disabled         = false;
  document.getElementById('btnStand').disabled       = false;
}


// Init max inputs
document.addEventListener('DOMContentLoaded', () => {
  ['miseSlots','miseBJ'].forEach(id => {
    const inp = document.getElementById(id);
    if (!inp) return;
    inp.max = Math.floor(soldeRaw);
    inp.addEventListener('input', () => {
      if (parseFloat(inp.value) > soldeRaw) inp.value = Math.floor(soldeRaw);
      if (parseFloat(inp.value) < 1) inp.value = 1;
    });
  });
});
</script>

</body>
</html>
