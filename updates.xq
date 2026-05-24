(: ════════════════════════════════════════════════════════════
   Fichier  : updates.xq
   Projet   : Mini-Projet XML — Club Info_Tech
   Cours    : Données semi-structurées — Licence 3 ISIL
   Description : 3 opérations XQuery Update Facility sur club.xml
   Note     : À exécuter sous BaseX (supporte XQuery Update)
   ════════════════════════════════════════════════════════════ :)


(: ──────────────────────────────────────────────────────────
   OPÉRATION 1 — INSERTION                        [1.5 pt]
   ────────────────────────────────────────────────────────── :)

declare option db:writeback "true";

insert node <membre id="M014" categorieRef="C2">
    <nom>Zerrouk</nom>
    <prenom>Lyna</prenom>
    <email>l.zerrouk@club.dz</email>
</membre>
into doc("C:C:\xampp\htdocs\miniproject_XML_Bouchaali-Imane_Hammi-Hadjer_G1\club.xml")//membres



,

(: ──────────────────────────────────────────────────────────
   OPÉRATION 2 — MODIFICATION                     [1.5 pt]
   ────────────────────────────────────────────────────────── :)




replace value of node doc("C:\xampp\htdocs\miniproject_XML_Bouchaali-Imane_Hammi-Hadjer_G1\club.xml")//concours[@id="CO3"]/@coefficient with "2.5"


,

(: ──────────────────────────────────────────────────────────
   OPÉRATION 3 — SUPPRESSION                      [1 pt]
   ────────────────────────────────────────────────────────── :)





delete node doc("C:\xampp\htdocs\miniproject_XML_Bouchaali-Imane_Hammi-Hadjer_G1\club.xml")//membre[@id="M014"]

