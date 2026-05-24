<?php
// ════════════════════════════════════════════════════════════
//  Club Info_Tech — Application Web
//  Fichier : web/index.php
//  Rôle    : Interface principale (concours, inscription,
//            résultats, requêtes libres)
//  Requires: club.xml dans le dossier parent (../)
// ════════════════════════════════════════════════════════════

$xml_path = 'club.xml';
$message  = '';
$msgType  = '';

// ── Charger le XML ──────────────────────────────────────────
function loadXML($path) {
    if (!file_exists($path)) return false;
    return simplexml_load_file($path);
}

// ── Calcul du score ─────────────────────────────────────────
function calcScore($complexite, $temps, $coeff) {
    return round(($complexite + $temps) * $coeff, 2);
}

// ── Comparaison pour tri décroissant par score ───────────────
function compare_scores_desc($a, $b) {
    if ($a['score'] === $b['score']) return 0;
    return ($a['score'] < $b['score']) ? 1 : -1;
}

// ════════════════════════════════════════════════════════════
//  TRAITEMENT : Nouvelle Inscription (POST)
// ════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscrire') {
    $concoursId  = htmlspecialchars($_POST['concours'] ?? '');
    $membreId    = htmlspecialchars($_POST['membre']   ?? '');
    $complexite  = intval($_POST['complexite'] ?? 0);
    $temps       = intval($_POST['temps']      ?? 0);

    if ($concoursId && $membreId && $complexite >= 0 && $complexite <= 100 && $temps > 0) {
        if (file_exists($xml_path)) {
            $xml = simplexml_load_file($xml_path);
            $targetConcours = null;

            // Trouver le concours cible
            foreach ($xml->concours->concours as $c) {
                if ((string)$c['id'] === $concoursId) {
                    $targetConcours = $c;
                    break;
                }
            }

            if ($targetConcours) {
                // Vérifier que le membre appartient à la bonne catégorie
                $concoursCateg = (string)$targetConcours['categorieRef'];
                $membreCateg   = '';
                foreach ($xml->membres->membre as $m) {
                    if ((string)$m['id'] === $membreId) {
                        $membreCateg = (string)$m['categorieRef'];
                        break;
                    }
                }

                // Vérifier si déjà inscrit
                $dejaInscrit = false;
                foreach ($targetConcours->participants->participant as $p) {
                    if ((string)$p['membreRef'] === $membreId) {
                        $dejaInscrit = true;
                        break;
                    }
                }

                if ($membreCateg !== $concoursCateg) {
                    $message = "❌ Ce membre n'appartient pas à la catégorie de ce concours.";
                    $msgType = 'error';
                } elseif ($dejaInscrit) {
                    $message = "⚠️ Ce membre est déjà inscrit à ce concours.";
                    $msgType = 'warning';
                } else {
                    // Ajouter le participant
                    $participant = $targetConcours->participants->addChild('participant');
                    $participant->addAttribute('membreRef', $membreId);
                    $participant->addChild('complexite',     $complexite);
                    $participant->addChild('tempsExecution', $temps);

                    // Sauvegarder avec DOM pour un formatage propre
                    $dom = dom_import_simplexml($xml)->ownerDocument;
                    $dom->formatOutput = true;
                    $dom->save($xml_path);

                    $message = "✅ Inscription réussie et sauvegardée !";
                    $msgType = 'success';
                }
            } else {
                $message = "❌ Concours introuvable.";
                $msgType = 'error';
            }
        }
    } else {
        $message = "❌ Veuillez remplir tous les champs correctement.";
        $msgType = 'error';
    }
}

// ════════════════════════════════════════════════════════════
//  CHARGEMENT DES DONNÉES
// ════════════════════════════════════════════════════════════
$xml = loadXML($xml_path);

// Concours sélectionné pour les résultats
$viewConcours = $_GET['view_concours'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Club Info_Tech — Gestion des Concours</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

<!-- ══ HEADER ══════════════════════════════════════════════ -->
<header>
  <div class="header-inner">
    <span class="trophy">🏆</span>
    <h1>Club Info_Tech <span class="sub">— Gestion des Concours</span></h1>
  </div>
  <nav>
    <a href="#concours-list">📅 Concours</a>
    <a href="#resultats">🥇 Résultats</a>
    <a href="#inscription">📝 Inscription</a>
    <a href="#requetes">🔍 Requêtes libres</a>
  </nav>
</header>

<main>

<?php if ($message): ?>
  <div class="alert alert-<?= $msgType ?>">
    <?= $message ?>
  </div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     SECTION 1 — LISTE DES CONCOURS
══════════════════════════════════════════════════════════ -->
<section id="concours-list" class="card">
  <h2>📅 Liste des Concours Disponibles</h2>

  <?php if ($xml): ?>
  <table>
    <thead>
      <tr>
        <th>Titre</th>
        <th>Date</th>
        <th>Catégorie</th>
        <th>Coefficient</th>
        <th>Participants</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Récupérer les concours et les trier par date
      $concoursList = [];
      foreach ($xml->concours->concours as $c) {
          $catLibelle = '';
          foreach ($xml->categories->categorie as $cat) {
              if ((string)$cat['id'] === (string)$c['categorieRef']) {
                  $catLibelle = (string)$cat['libelle'];
                  break;
              }
          }
          $concoursList[] = [
              'id'    => (string)$c['id'],
              'titre' => (string)$c->titre,
              'date'  => (string)$c['date'],
              'coeff' => (string)$c['coefficient'],
              'cat'   => $catLibelle,
              'nb'    => count($c->participants->participant),
          ];
      }
      usort($concoursList, fn($a,$b) => strcmp($a['date'], $b['date']));

      foreach ($concoursList as $row): ?>
      <tr>
        <td><strong><?= htmlspecialchars($row['titre']) ?></strong></td>
        <td><?= htmlspecialchars($row['date']) ?></td>
        <td><span class="badge"><?= htmlspecialchars($row['cat']) ?></span></td>
        <td><?= htmlspecialchars($row['coeff']) ?></td>
        <td><?= $row['nb'] ?> participant(s)</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p class="empty">⚠️ Impossible de charger club.xml. Vérifiez le chemin : <code><?= $xml_path ?></code></p>
  <?php endif; ?>
</section>

<!-- ══════════════════════════════════════════════════════════
     SECTION 2 — RÉSULTATS D'UN CONCOURS
══════════════════════════════════════════════════════════ -->
<section id="resultats" class="card">
  <h2>🥇 Résultats des Concours</h2>

  <form method="GET" action="#results-anchor" class="inline-form">
    <select name="view_concours">
      <option value="">Sélectionnez un concours...</option>
      <?php if ($xml): foreach ($xml->concours->concours as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $viewConcours === (string)$c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)$c->titre) ?>
        </option>
      <?php endforeach; endif; ?>
    </select>
    <button type="submit" class="btn-primary">Afficher résultats</button>
  </form>

  <a name="results-anchor"></a>

  <?php if ($viewConcours && $xml):
    $targetC = null;
    foreach ($xml->concours->concours as $c) {
        if ((string)$c['id'] === $viewConcours) { $targetC = $c; break; }
    }
    if ($targetC):
        $coeff   = (float)$targetC['coefficient'];
        $results = [];
        foreach ($targetC->participants->participant as $p) {
            $mRef  = (string)$p['membreRef'];
            $nom   = ''; $prenom = '';
            foreach ($xml->membres->membre as $m) {
                if ((string)$m['id'] === $mRef) {
                    $nom    = (string)$m->nom;
                    $prenom = (string)$m->prenom;
                    break;
                }
            }
            $score     = calcScore((int)$p->complexite, (int)$p->tempsExecution, $coeff);
            $results[] = [
                'nom'        => $nom,
                'prenom'     => $prenom,
                'complexite' => (int)$p->complexite,
                'temps'      => (int)$p->tempsExecution,
                'score'      => $score,
            ];
        }
        usort($results, 'compare_scores_desc');
        $maxScore = count($results) > 0 ? $results[0]['score'] : 0;
  ?>
  <h3>Résultats : <?= htmlspecialchars((string)$targetC->titre) ?></h3>
  <p class="coeff-info">Coefficient : <strong><?= $coeff ?></strong> — Formule : <em>score = (complexité + temps) × coefficient</em></p>
  <table>
    <thead>
      <tr>
        <th>Rang</th>
        <th>Participant</th>
        <th>Complexité</th>
        <th>Temps (ms)</th>
        <th>Score</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($results as $i => $r): $isWinner = ($r['score'] === $maxScore); ?>
      <tr class="<?= $isWinner ? 'winner-row' : '' ?>">
        <td>
          <?= $i + 1 ?>
          <?php if ($isWinner): ?> <span class="crown">👑</span><?php endif; ?>
        </td>
        <td><?= htmlspecialchars($r['nom'] . ' ' . $r['prenom']) ?></td>
        <td><?= $r['complexite'] ?></td>
        <td><?= $r['temps'] ?></td>
        <td><strong><?= number_format($r['score'], 2) ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p class="empty">Concours introuvable.</p>
  <?php endif;
  endif; ?>
</section>

<!-- ══════════════════════════════════════════════════════════
     SECTION 3 — NOUVELLE INSCRIPTION
══════════════════════════════════════════════════════════ -->
<section id="inscription" class="card">
  <h2>📝 Nouvelle Inscription</h2>

  <form method="POST" action="#inscription" class="form-grid">
    <input type="hidden" name="action" value="inscrire"/>

    <div class="form-group">
      <label for="concours">Concours :</label>
      <select name="concours" id="concours" required>
        <option value="">Sélectionnez...</option>
        <?php if ($xml): foreach ($xml->concours->concours as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars((string)$c->titre) ?></option>
        <?php endforeach; endif; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="membre">Membre :</label>
      <select name="membre" id="membre" required>
        <option value="">Sélectionnez...</option>
        <?php if ($xml): foreach ($xml->membres->membre as $m): ?>
          <option value="<?= $m['id'] ?>">
            [<?= $m['id'] ?>] <?= htmlspecialchars((string)$m->prenom . ' ' . (string)$m->nom) ?>
          </option>
        <?php endforeach; endif; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="complexite">Complexité de l'algo (0–100) :</label>
      <input type="number" name="complexite" id="complexite"
             min="0" max="100" placeholder="Ex: 85" required/>
    </div>

    <div class="form-group">
      <label for="temps">Temps d'exécution (ms) :</label>
      <input type="number" name="temps" id="temps"
             min="1" placeholder="Ex: 120" required/>
    </div>

    <div class="form-group full-width">
      <button type="submit" class="btn-primary btn-large">S'inscrire</button>
    </div>
  </form>
</section>

<!-- ══════════════════════════════════════════════════════════
     SECTION 4 — REQUÊTES LIBRES (XPath + XQuery)
══════════════════════════════════════════════════════════ -->
<section id="requetes" class="card">
  <h2>🔍 Requêtes Libres</h2>

  <?php
  $basexUrl    = 'http://localhost:8080/rest';
  $basexUser   = 'isil';
  $basexPass   = 'admin';
  $serverOnline = false;
  $ch = curl_init($basexUrl);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>2,CURLOPT_USERPWD=>"$basexUser:$basexPass"]);
  curl_exec($ch);
  $serverOnline = (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200 || curl_getinfo($ch, CURLINFO_HTTP_CODE) === 400);
  curl_close($ch);

  $queryType = $_GET['qtype'] ?? 'xpath';
  ?>

  <!-- Sélecteur XPath / XQuery -->
  <div style="display:flex; gap:12px; margin-bottom:16px;">
    <a href="?qtype=xpath#requetes"
       class="btn-primary" style="<?= $queryType==='xpath' ? '' : 'opacity:0.5;' ?>">
      🔍 XPath
    </a>
    <a href="?qtype=xquery#requetes"
       class="btn-primary" style="<?= $queryType==='xquery' ? '' : 'opacity:0.5;' ?>">
      ⚡ XQuery
    </a>
  </div>

  <?php if ($queryType === 'xpath'): ?>

    <p class="info-note">
      ℹ️ XPath — recherche directe dans club.xml. Exemples :
      <code>//membre[nom='Benali']</code> &nbsp;|&nbsp;
      <code>//concours[@coefficient > 1.4]</code> &nbsp;|&nbsp;
      <code>//membre[@categorieRef='C1']</code>
    </p>

    <form method="GET" action="#requetes" class="inline-form">
      <input type="hidden" name="qtype" value="xpath"/>
      <input type="text" name="xpath" id="xpath"
             value="<?= htmlspecialchars($_GET['xpath'] ?? '') ?>"
             placeholder="Entrez une expression XPath..."
             style="flex:1; min-width:300px;"/>
      <button type="submit" class="btn-primary">Exécuter</button>
    </form>

    <?php
    $xpathQuery = $_GET['xpath'] ?? '';
    if ($xpathQuery && $xml):
        $results = @$xml->xpath($xpathQuery);
        if ($results === false || $results === null): ?>
      <div class="alert alert-error">❌ Expression XPath invalide.</div>
    <?php elseif (count($results) === 0): ?>
      <p class="empty">Aucun résultat.</p>
    <?php else: ?>
      <p><strong><?= count($results) ?> résultat(s) :</strong></p>
      <div class="xml-result">
        <?php foreach ($results as $node): ?>
          <pre><?= htmlspecialchars($node->asXML()) ?></pre>
        <?php endforeach; ?>
      </div>
    <?php endif;
    endif; ?>

  <?php else: ?>

    <p class="info-note">
      ℹ️ XQuery — requête complète via BaseX server. Exemples :
      <code>for $m in doc("club.xml")//membre return $m/nom</code> &nbsp;|&nbsp;
      <code>count(doc("club.xml")//membre)</code>
    </p>

    <?php if (!$serverOnline): ?>
      <div class="alert alert-warning">
        ⚠️ BaseX server hors ligne. Lancez <code>basexhttp.bat</code> pour activer XQuery.
      </div>
    <?php endif; ?>

    <form method="GET" action="#requetes" class="inline-form">
      <input type="hidden" name="qtype" value="xquery"/>
      <textarea name="xquery" rows="3"
                style="flex:1; min-width:300px; padding:10px; border:2px solid #DDE3F0;
                       border-radius:8px; font-family:monospace; font-size:0.9rem; resize:vertical;"
                placeholder="Entrez une requête XQuery..."><?= htmlspecialchars($_GET['xquery'] ?? '') ?></textarea>
      <button type="submit" class="btn-primary" <?= !$serverOnline ? 'disabled' : '' ?>>Exécuter</button>
    </form>

    <?php
    $xqueryInput = $_GET['xquery'] ?? '';
    if ($xqueryInput && $serverOnline):
        $ch = curl_init($basexUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERPWD        => "$basexUser:$basexPass",
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xqueryInput,
            CURLOPT_POSTFIELDS     => '<query xmlns="http://basex.org/rest"><text><![CDATA[' . $xqueryInput . ']]></text></query>',
        ]);
        $response = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200): ?>
          <p><strong>Résultat :</strong></p>
          <div class="xml-result">
            <pre><?= htmlspecialchars($response) ?></pre>
          </div>
        <?php else: ?>
          <div class="alert alert-error">❌ Erreur XQuery : <?= htmlspecialchars($response) ?></div>
        <?php endif;
    endif; ?>

  <?php endif; ?>
</section>

</main>

<footer>
  <p>Club Info_Tech &copy; 2026 — Mini-Projet XML / XSD / XQuery — Licence 3 ISIL</p>
</footer>

</body>
</html>
