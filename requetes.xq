(: ════════════════════════════════════════════════════════════
   Fichier  : requetes.xq
   Projet   : Mini-Projet XML — Club Info_Tech
   Cours    : Données semi-structurées — Licence 3 ISIL
   Description : 5 requêtes XQuery sur club.xml
   ════════════════════════════════════════════════════════════ :)

(: ──────────────────────────────────────────────────────────
   Q1 — Liste complète des membres                  [1 pt]
   Affiche pour chaque membre : id, nom complet,
   email et libellé de la catégorie (pas son ID).
   ────────────────────────────────────────────────────────── :)
(: Q1 :)

let $doc := doc("club.xml")/club   (: chargement du document racine :)

return
<membres> {
  (: FLWOR : on parcourt chaque membre :)
  for $m in $doc/membres/membre

    (: jointure : on récupère la catégorie dont l'id = categorieRef du membre :)
    let $cat := $doc/categories/categorie[@id = $m/@categorieRef]

  return
    <membre id="{$m/@id}">
      <nomComplet>{ string($m/prenom), " ", string($m/nom) }</nomComplet>
      <email>{ string($m/email) }</email>
      <categorie>{ string($cat/@libelle) }</categorie>
    </membre>
} </membres>

,
(: séparateur entre requêtes :)

(: ──────────────────────────────────────────────────────────
   Q2 — Liste des concours triés par date             [1 pt]
   Affiche titre, date, coefficient et libellé de
   la catégorie concernée. Tri par date croissante.
   ────────────────────────────────────────────────────────── :)
(: Q2 :)

let $doc := doc("club.xml")/club

return
<concours> {
  (: FLWOR avec order by sur la date :)
  for $c in $doc/concours/concours

    (: jointure : libellé de la catégorie du concours :)
    let $cat := $doc/categories/categorie[@id = $c/@categorieRef]

  order by xs:date($c/@date) ascending   (: tri par date croissante :)

  return
    <concours id="{$c/@id}">
      <titre>{ string($c/titre) }</titre>
      <date>{ string($c/@date) }</date>
      <coefficient>{ string($c/@coefficient) }</coefficient>
      <categorie>{ string($cat/@libelle) }</categorie>
    </concours>
} </concours>

,

(: ──────────────────────────────────────────────────────────
   Q3 — Calcul du score de chaque participant         [2 pts]
   Formule : score = (complexite + tempsExecution) × coefficient
   Affiche : titre du concours, nom du participant,
   complexité, temps, score arrondi à 2 décimales.
   ────────────────────────────────────────────────────────── :)
(: Q3 :)

let $doc := doc("club.xml")/club

return
<resultats> {
  (: boucle externe : chaque concours :)
  for $c in $doc/concours/concours

    let $coeff := xs:decimal($c/@coefficient)   (: coefficient du concours :)

  return
    <concours titre="{$c/titre}"> {
      (: boucle interne : chaque participant du concours :)
      for $p in $c/participants/participant

        (: jointure : on retrouve le membre par son id :)
        let $m     := $doc/membres/membre[@id = $p/@membreRef]

        (: calcul du score selon la formule du sujet :)
        let $score := (xs:integer($p/complexite) + xs:integer($p/tempsExecution))
                      * $coeff

      return
        <participant>
          <nom>{ string($m/nom), " ", string($m/prenom) }</nom>
          <complexite>{ string($p/complexite) }</complexite>
          <tempsExecution>{ string($p/tempsExecution) }</tempsExecution>
          (: arrondi à 2 décimales avec format-number :)
          <score>{ format-number($score, "0.00") }</score>
        </participant>
    } </concours>
} </resultats>

,

(: ──────────────────────────────────────────────────────────
   Q4 — Vainqueur de chaque concours                 [2 pts]
   Le participant avec le score maximum.
   En cas d'égalité : tous les ex-aequo sont affichés.
   ────────────────────────────────────────────────────────── :)
(: Q4 :)

let $doc := doc("club.xml")/club

return
<vainqueurs> {
  for $c in $doc/concours/concours

    let $coeff := xs:decimal($c/@coefficient)

    (: calcul des scores de tous les participants du concours :)
    let $scores :=
      for $p in $c/participants/participant
      return (xs:integer($p/complexite) + xs:integer($p/tempsExecution)) * $coeff

    (: score maximum du concours :)
    let $maxScore := max($scores)

  return
    <concours titre="{$c/titre}"> {
      (: filtre : on garde uniquement les participants dont le score = max :)
      for $p in $c/participants/participant

        let $m     := $doc/membres/membre[@id = $p/@membreRef]
        let $score := (xs:integer($p/complexite) + xs:integer($p/tempsExecution))
                      * $coeff

      where $score = $maxScore   (: condition ex-aequo incluse :)

      return
        <vainqueur>
          <nom>{ string($m/nom) }</nom>
          <prenom>{ string($m/prenom) }</prenom>
          <score>{ format-number($score, "0.00") }</score>
        </vainqueur>
    } </concours>
} </vainqueurs>

,

(: ──────────────────────────────────────────────────────────
   Q5 — Membres d'une catégorie paramétrée           [2 pts]
   Variable $categorie : libellé à modifier selon besoin.
   Résultat trié alphabétiquement par nom puis prénom.
   ────────────────────────────────────────────────────────── :)
(: Q5 :)

let $doc := doc("club.xml")/club

(: ← modifiez ici le libellé de la catégorie désirée :)
let $categorie := "Intelligence Artificielle"

(: récupération de l'id de la catégorie correspondant au libellé :)
let $catId := $doc/categories/categorie[@libelle = $categorie]/@id

return
<membres categorie="{$categorie}"> {
  (: FLWOR avec double order by : nom puis prénom :)
  for $m in $doc/membres/membre[@categorieRef = $catId]

  order by string($m/nom) ascending,        (: tri alphabétique par nom :)
           string($m/prenom) ascending       (: puis par prénom :)

  return
    <membre id="{$m/@id}">
      <nom>{ string($m/nom) }</nom>
      <prenom>{ string($m/prenom) }</prenom>
      <email>{ string($m/email) }</email>
    </membre>
} </membres>
