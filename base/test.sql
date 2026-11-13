-- ============================================================
-- SIRH « TalentFlow » — Tests de cohérence SQL
-- Fichier : base/test.sql
--
-- À exécuter APRÈS base/init.sql puis base/data.sql :
--   mysql -uroot < base/init.sql
--   mysql -uroot < base/data.sql
--   mysql -uroot < base/test.sql
--
-- Le script ne modifie aucune donnée métier. Il collecte les
-- contrôles dans une table temporaire, affiche les échecs, puis
-- provoque volontairement une erreur SQL s'il en reste au moins un.
-- ============================================================

USE rh;

DROP TEMPORARY TABLE IF EXISTS tf_resultats;
CREATE TEMPORARY TABLE tf_resultats (
    id INT UNSIGNED PRIMARY KEY,
    libelle VARCHAR(255) NOT NULL,
    ok TINYINT NOT NULL DEFAULT 0
);

-- ============================================================
-- 1. SOCLE, RÔLES ET PERMISSIONS
-- ============================================================

INSERT INTO tf_resultats (id, libelle, ok)
SELECT 1, 'Les 5 rôles attendus existent.', (SELECT COUNT(*) FROM role WHERE libelle IN ('dg', 'rh', 'manager', 'employe', 'candidat')) = 5
UNION ALL SELECT 2, 'La matrice de permissions couvre les modules du socle.', (SELECT COUNT(*) FROM permission_module) >= 10
UNION ALL SELECT 3, 'Le DG peut valider les besoins.', (SELECT COUNT(*) FROM role_permission WHERE role_id = 1 AND module = 'besoins' AND action = 'valider') = 1
UNION ALL SELECT 4, 'Le RH peut valider les besoins.', (SELECT COUNT(*) FROM role_permission WHERE role_id = 2 AND module = 'besoins' AND action = 'valider') = 1
UNION ALL SELECT 5, 'Le manager peut créer un besoin pour son équipe.', (SELECT COUNT(*) FROM role_permission WHERE role_id = 3 AND module = 'besoins' AND action = 'creer' AND portee = 'equipe') = 1
UNION ALL SELECT 6, 'Le manager ne peut pas valider un besoin.', (SELECT COUNT(*) FROM role_permission WHERE role_id = 3 AND module = 'besoins' AND action = 'valider') = 0
UNION ALL SELECT 7, 'Un employé n’accède pas aux besoins.', (SELECT COUNT(*) FROM role_permission WHERE role_id = 4 AND module = 'besoins') = 0
UNION ALL SELECT 8, 'Seuls le manager et le DG créent des besoins.', (SELECT COUNT(*) FROM role_permission WHERE role_id IN (2, 4, 5) AND module = 'besoins' AND action = 'creer') = 0
UNION ALL SELECT 9, 'RH et DG accèdent à l’administration des budgets.', (SELECT COUNT(*) FROM role_permission WHERE role_id IN (1, 2) AND module = 'admin' AND action = 'voir') = 2;

-- ============================================================
-- 2. UTILISATEURS DE DÉMONSTRATION
-- ============================================================

INSERT INTO tf_resultats (id, libelle, ok)
SELECT 10, 'Les 5 comptes de démonstration sont actifs.', (SELECT COUNT(*) FROM utilisateur WHERE email IN ('dg@mail.com', 'rh@mail.com', 'manager@mail.com', 'employe@mail.com', 'candidat@mail.com') AND actif = 1) = 5
UNION ALL SELECT 11, 'Les mots de passe de démonstration sont hachés (bcrypt).', (SELECT COUNT(*) FROM utilisateur WHERE email IN ('dg@mail.com', 'rh@mail.com', 'manager@mail.com', 'employe@mail.com', 'candidat@mail.com') AND mdp NOT LIKE '$2y$%') = 0
UNION ALL SELECT 12, 'Chaque utilisateur a un rôle valide.', (SELECT COUNT(*) FROM utilisateur u LEFT JOIN role r ON r.id = u.role_id WHERE r.id IS NULL) = 0;

-- ============================================================
-- 3. RÉFÉRENTIELS ET BUDGETS
-- ============================================================

INSERT INTO tf_resultats (id, libelle, ok)
SELECT 13, 'Trois départements de démonstration sont actifs.', (SELECT COUNT(*) FROM departement WHERE actif = 1) = 3
UNION ALL SELECT 14, 'Les budgets 2026 sont renseignés.', (SELECT COUNT(*) FROM budget_departement WHERE annee = 2026) >= 3
UNION ALL SELECT 15, 'Le budget 2026 du département Informatique est de 300 000 000 Ar.', (SELECT montant_annuel FROM budget_departement bd JOIN departement d ON d.id = bd.departement_id WHERE d.code = 'INFO' AND bd.annee = 2026) = 300000000
UNION ALL SELECT 16, 'Aucun budget ne référence un département inexistant.', (SELECT COUNT(*) FROM budget_departement bd LEFT JOIN departement d ON d.id = bd.departement_id WHERE d.id IS NULL) = 0;

-- ============================================================
-- 4. STATUTS ET WORKFLOW DES BESOINS (BLOC 1)
-- ============================================================

INSERT INTO tf_resultats (id, libelle, ok)
SELECT 17, 'Les statuts de besoin du Bloc 1 existent.', (SELECT COUNT(*) FROM statut_besoin WHERE libelle IN ('En attente RH', 'En attente DG', 'Validé', 'Refusé')) = 4
UNION ALL SELECT 18, 'Chaque besoin référence un poste, un département, un demandeur et un statut valides.', (SELECT COUNT(*) FROM besoin b LEFT JOIN poste p ON p.id = b.poste_id LEFT JOIN departement d ON d.id = b.departement_id LEFT JOIN utilisateur u ON u.id = b.demandeur_id LEFT JOIN statut_besoin sb ON sb.id = b.statut_id WHERE p.id IS NULL OR d.id IS NULL OR u.id IS NULL OR sb.id IS NULL) = 0
UNION ALL SELECT 19, 'Chaque besoin a exactement les 3 étapes workflow rh, finance, dg.', (SELECT COUNT(*) FROM besoin b WHERE (SELECT COUNT(DISTINCT w.etape) FROM workflow_validation w WHERE w.objet_type = 'besoin' AND w.objet_id = b.id) <> 3) = 0
UNION ALL SELECT 20, 'Chaque étape finance contient l’instantané budgétaire complet.', (SELECT COUNT(*) FROM workflow_validation w WHERE w.objet_type = 'besoin' AND w.etape = 'finance' AND (w.details_json IS NULL OR JSON_EXTRACT(w.details_json, '$.budget_annuel') IS NULL OR JSON_EXTRACT(w.details_json, '$.masse_salariale') IS NULL OR JSON_EXTRACT(w.details_json, '$.engagements') IS NULL OR JSON_EXTRACT(w.details_json, '$.cout') IS NULL OR JSON_EXTRACT(w.details_json, '$.budget_disponible') IS NULL OR JSON_EXTRACT(w.details_json, '$.reste_apres_cout') IS NULL)) = 0
UNION ALL SELECT 21, 'L’étape finance est toujours automatique.', (SELECT COUNT(*) FROM workflow_validation WHERE objet_type = 'besoin' AND etape = 'finance' AND automatique <> 1) = 0
UNION ALL SELECT 22, 'Un besoin « En attente RH » a une étape RH en attente.', (SELECT COUNT(*) FROM besoin b JOIN statut_besoin sb ON sb.id = b.statut_id JOIN workflow_validation w ON w.objet_type = 'besoin' AND w.objet_id = b.id AND w.etape = 'rh' WHERE sb.libelle = 'En attente RH' AND w.decision <> 'en_attente') = 0
UNION ALL SELECT 23, 'Un besoin « En attente DG » a RH et finance traités, et DG en attente.', (SELECT COUNT(*) FROM besoin b JOIN statut_besoin sb ON sb.id = b.statut_id JOIN workflow_validation rh ON rh.objet_type = 'besoin' AND rh.objet_id = b.id AND rh.etape = 'rh' JOIN workflow_validation fi ON fi.objet_type = 'besoin' AND fi.objet_id = b.id AND fi.etape = 'finance' JOIN workflow_validation dg ON dg.objet_type = 'besoin' AND dg.objet_id = b.id AND dg.etape = 'dg' WHERE sb.libelle = 'En attente DG' AND (rh.decision = 'en_attente' OR fi.decision = 'en_attente' OR dg.decision <> 'en_attente')) = 0
UNION ALL SELECT 24, 'Un besoin Validé ou Refusé a une décision DG enregistrée.', (SELECT COUNT(*) FROM besoin b JOIN statut_besoin sb ON sb.id = b.statut_id JOIN workflow_validation dg ON dg.objet_type = 'besoin' AND dg.objet_id = b.id AND dg.etape = 'dg' WHERE sb.libelle IN ('Validé', 'Refusé') AND dg.decision = 'en_attente') = 0
UNION ALL SELECT 25, 'Le statut final du besoin reflète la décision finale du DG.', (SELECT COUNT(*) FROM besoin b JOIN statut_besoin sb ON sb.id = b.statut_id JOIN workflow_validation dg ON dg.objet_type = 'besoin' AND dg.objet_id = b.id AND dg.etape = 'dg' WHERE (sb.libelle = 'Validé' AND dg.decision <> 'accepte') OR (sb.libelle = 'Refusé' AND dg.decision <> 'refuse')) = 0
UNION ALL SELECT 26, 'Un besoin clôturé a une date_cloture.', (SELECT COUNT(*) FROM besoin b JOIN statut_besoin sb ON sb.id = b.statut_id WHERE sb.libelle IN ('Validé', 'Refusé') AND b.date_cloture IS NULL) = 0
UNION ALL SELECT 27, 'Aucune étape workflow de besoin n’est orpheline.', (SELECT COUNT(*) FROM workflow_validation w LEFT JOIN besoin b ON b.id = w.objet_id WHERE w.objet_type = 'besoin' AND b.id IS NULL) = 0;

-- ============================================================
-- 5. SCÉNARIO DE DÉMONSTRATION DU BLOC 1
-- ============================================================

INSERT INTO tf_resultats (id, libelle, ok)
SELECT 28, 'Le besoin de démonstration #1 est validé.', (SELECT COUNT(*) FROM besoin b JOIN statut_besoin sb ON sb.id = b.statut_id WHERE b.id = 1 AND sb.libelle = 'Validé') = 1
UNION ALL SELECT 29, 'Le besoin #1 a une décision DG favorable.', (SELECT COUNT(*) FROM workflow_validation WHERE objet_type = 'besoin' AND objet_id = 1 AND etape = 'dg' AND decision = 'accepte') = 1
UNION ALL SELECT 30, 'Le besoin #2 illustre la file RH.', (SELECT COUNT(*) FROM besoin b JOIN statut_besoin sb ON sb.id = b.statut_id WHERE b.id = 2 AND sb.libelle = 'En attente RH') = 1
UNION ALL SELECT 31, 'Le besoin #3 illustre la file DG.', (SELECT COUNT(*) FROM besoin b JOIN statut_besoin sb ON sb.id = b.statut_id WHERE b.id = 3 AND sb.libelle = 'En attente DG') = 1
UNION ALL SELECT 32, 'L’instantané finance du besoin #1 rappelle le budget annuel.', (SELECT JSON_UNQUOTE(JSON_EXTRACT(details_json, '$.budget_annuel')) FROM workflow_validation WHERE objet_type = 'besoin' AND objet_id = 1 AND etape = 'finance') = '300000000'
UNION ALL SELECT 33, 'L’instantané finance du besoin #1 contient le coût annuel (7 200 000 Ar).', (SELECT JSON_UNQUOTE(JSON_EXTRACT(details_json, '$.cout')) FROM workflow_validation WHERE objet_type = 'besoin' AND objet_id = 1 AND etape = 'finance') = '7200000'
UNION ALL SELECT 34, 'L’instantané finance du besoin #1 contient le reste après coût (255 600 000 Ar).', (SELECT JSON_UNQUOTE(JSON_EXTRACT(details_json, '$.reste_apres_cout')) FROM workflow_validation WHERE objet_type = 'besoin' AND objet_id = 1 AND etape = 'finance') = '255600000';

-- ============================================================
-- 6. COHÉRENCE BUDGÉTAIRE DU DÉPARTEMENT INFORMATIQUE
-- ============================================================

INSERT INTO tf_resultats (id, libelle, ok)
SELECT 35, 'La masse salariale annuelle du département Informatique est de 37 200 000 Ar.', (SELECT COALESCE(SUM(c.salaire_mensuel), 0) * 12 FROM salarie s JOIN contrat c ON c.salarie_id = s.id JOIN departement d ON d.id = s.departement_id WHERE d.code = 'INFO' AND s.actif = 1 AND c.statut = 'Accepte') = 37200000
UNION ALL SELECT 36, 'Les engagements 2026 du département Informatique sont de 7 200 000 Ar.', (SELECT COALESCE(SUM(b.nombre * b.salaire_estime * 12), 0) FROM besoin b JOIN statut_besoin sb ON sb.id = b.statut_id JOIN departement d ON d.id = b.departement_id WHERE d.code = 'INFO' AND sb.libelle = 'Validé' AND YEAR(b.date_creation) = 2026) = 7200000
UNION ALL SELECT 37, 'Le budget disponible 2026 du département Informatique est de 255 600 000 Ar.', (SELECT bd.montant_annuel - (SELECT COALESCE(SUM(c.salaire_mensuel), 0) * 12 FROM salarie s JOIN contrat c ON c.salarie_id = s.id WHERE s.departement_id = bd.departement_id AND s.actif = 1 AND c.statut = 'Accepte') - (SELECT COALESCE(SUM(b.nombre * b.salaire_estime * 12), 0) FROM besoin b JOIN statut_besoin sb ON sb.id = b.statut_id WHERE b.departement_id = bd.departement_id AND sb.libelle = 'Validé' AND YEAR(b.date_creation) = bd.annee) FROM budget_departement bd JOIN departement d ON d.id = bd.departement_id WHERE d.code = 'INFO' AND bd.annee = 2026) = 255600000;

-- ============================================================
-- 7. CHAÎNAGE BESOIN → ANNONCE → CANDIDATURE
-- ============================================================

INSERT INTO tf_resultats (id, libelle, ok)
SELECT 38, 'Chaque annonce référence un besoin existant.', (SELECT COUNT(*) FROM annonce a LEFT JOIN besoin b ON b.id = a.besoin_id WHERE b.id IS NULL) = 0
UNION ALL SELECT 39, 'Un besoin n’a pas plusieurs annonces.', (SELECT COUNT(*) FROM (SELECT besoin_id FROM annonce GROUP BY besoin_id HAVING COUNT(*) > 1) doublons) = 0
UNION ALL SELECT 40, 'Chaque candidature référence une annonce et un CV valides.', (SELECT COUNT(*) FROM candidature c LEFT JOIN annonce a ON a.id = c.annonce_id LEFT JOIN cv v ON v.id = c.cv_id WHERE a.id IS NULL OR v.id IS NULL) = 0
UNION ALL SELECT 41, 'Le scénario de démonstration comporte une annonce issue d’un besoin validé.', (SELECT COUNT(*) FROM annonce a JOIN besoin b ON b.id = a.besoin_id JOIN statut_besoin sb ON sb.id = b.statut_id WHERE sb.libelle = 'Validé') >= 1;

-- ============================================================
-- 8. TRAÇABILITÉ
-- ============================================================

INSERT INTO tf_resultats (id, libelle, ok)
SELECT 42, 'Le journal d’audit contient des traces de démonstration.', (SELECT COUNT(*) FROM audit_log) >= 1
UNION ALL SELECT 43, 'Les traces d’audit référencent un utilisateur valide.', (SELECT COUNT(*) FROM audit_log a LEFT JOIN utilisateur u ON u.id = a.utilisateur_id WHERE u.id IS NULL) = 0
UNION ALL SELECT 44, 'Les notifications référencent un utilisateur valide.', (SELECT COUNT(*) FROM notification n LEFT JOIN utilisateur u ON u.id = n.utilisateur_id WHERE u.id IS NULL) = 0
UNION ALL SELECT 45, 'L’historique des statuts de besoin n’est pas orphelin.', (SELECT COUNT(*) FROM historique_statut_besoin h LEFT JOIN besoin b ON b.id = h.besoin_id WHERE b.id IS NULL) = 0;

-- ============================================================
-- 9. BLOC 2 — ANNONCES, CANDIDATURES ET ENTRETIENS
-- ============================================================

INSERT INTO tf_resultats (id, libelle, ok)
SELECT 46, 'Les statuts de candidature du Bloc 2 existent.', (SELECT COUNT(*) FROM statut_candidature WHERE libelle IN ('Reçue', 'Présélectionnée', 'En attente entretien', 'Retenue', 'Non retenue')) = 5
UNION ALL SELECT 47, 'Le manager ne dispose pas de la permission Annonces.', (SELECT COUNT(*) FROM role_permission WHERE role_id = 3 AND module = 'annonces') = 0
UNION ALL SELECT 48, 'Toute annonce est rattachée à un besoin validé.', (SELECT COUNT(*) FROM annonce a JOIN besoin b ON b.id = a.besoin_id JOIN statut_besoin sb ON sb.id = b.statut_id WHERE sb.libelle <> 'Validé' AND sb.libelle <> 'Pourvu') = 0
UNION ALL SELECT 49, 'Un candidat ne peut postuler qu’une fois à une même annonce.', (SELECT COUNT(*) FROM (SELECT annonce_id, utilisateur_id FROM candidature GROUP BY annonce_id, utilisateur_id HAVING COUNT(*) > 1) doublons) = 0
UNION ALL SELECT 50, 'Chaque candidature possède un historique de statut initial.', (SELECT COUNT(*) FROM candidature c WHERE NOT EXISTS (SELECT 1 FROM historique_statut_candidature h WHERE h.candidature_id = c.id)) = 0
UNION ALL SELECT 51, 'Les langues de CV référencent des langues et niveaux valides.', (SELECT COUNT(*) FROM cv_langue cl LEFT JOIN langue l ON l.id = cl.langue_id LEFT JOIN niveau n ON n.id = cl.niveau_id WHERE l.id IS NULL OR n.id IS NULL) = 0
UNION ALL SELECT 52, 'Chaque entretien possède un statut et une réponse candidat.', (SELECT COUNT(*) FROM entretien e LEFT JOIN statut_entretien se ON se.id = e.statut_id WHERE se.id IS NULL OR e.reponse_candidat NOT IN ('en_attente', 'accepte', 'refuse')) = 0
UNION ALL SELECT 53, 'Chaque évaluation est liée à une candidature et un entretien valides.', (SELECT COUNT(*) FROM evaluation_candidat ev LEFT JOIN candidature c ON c.id = ev.candidature_id LEFT JOIN entretien e ON e.id = ev.entretien_id WHERE c.id IS NULL OR e.id IS NULL) = 0
UNION ALL SELECT 54, 'Les entretiens de démonstration sont planifiés pour la candidature #1.', (SELECT COUNT(*) FROM entretien WHERE candidature_id = 1 AND reponse_candidat = 'en_attente') >= 1
UNION ALL SELECT 55, 'Aucune annonce ouverte ne dépasse son nombre de postes pourvus.', (SELECT COUNT(*) FROM annonce a JOIN besoin b ON b.id = a.besoin_id WHERE a.est_cloture = 0 AND (SELECT COUNT(*) FROM candidature c JOIN statut_candidature sc ON sc.id = c.statut_id WHERE c.annonce_id = a.id AND sc.libelle IN ('Retenue', 'Embauchée')) >= b.nombre) = 0;

-- ============================================================
-- 10. BLOC 3 — PROPOSITION, NÉGOCIATION ET CONFIRMATION
-- ============================================================

INSERT INTO tf_resultats (id, libelle, ok)
SELECT 56, 'Chaque proposition possède exactement les 5 éléments du questionnaire.', (SELECT COUNT(*) FROM proposition_embauche p WHERE (SELECT COUNT(*) FROM proposition_element pe WHERE pe.proposition_id = p.id) <> 5) = 0
UNION ALL SELECT 57, 'Les 5 codes d’éléments attendus sont présents pour chaque proposition.', (SELECT COUNT(*) FROM (SELECT p.id FROM proposition_embauche p LEFT JOIN proposition_element pe ON pe.proposition_id = p.id AND pe.code IN ('salaire', 'type_contrat', 'date_debut', 'horaires', 'periode_essai') GROUP BY p.id HAVING COUNT(DISTINCT pe.code) <> 5) manquants) = 0
UNION ALL SELECT 58, 'Une proposition confirmable ou confirmée a nécessairement été envoyée.', (SELECT COUNT(*) FROM proposition_embauche WHERE statut IN ('confirmable', 'confirmee') AND date_envoi IS NULL) = 0
UNION ALL SELECT 59, 'Une contre-proposition possède les étapes RH et DG.', (SELECT COUNT(*) FROM proposition_element pe WHERE pe.statut IN ('contre_proposition', 'contre_validee', 'contre_refusee') AND (SELECT COUNT(DISTINCT wv.etape) FROM workflow_validation wv WHERE wv.objet_type = 'proposition_element' AND wv.objet_id = pe.id AND wv.etape IN ('rh', 'dg')) <> 2) = 0
UNION ALL SELECT 60, 'Une contre-proposition validée possède une valeur finale.', (SELECT COUNT(*) FROM proposition_element WHERE statut = 'contre_validee' AND (valeur_finale IS NULL OR valeur_finale = '')) = 0
UNION ALL SELECT 61, 'Une proposition confirmée possède un contrat généré.', (SELECT COUNT(*) FROM proposition_embauche p JOIN candidature c ON c.id = p.candidature_id LEFT JOIN contrat ct ON ct.candidature_id = c.id WHERE p.statut = 'confirmee' AND ct.id IS NULL) = 0
UNION ALL SELECT 62, 'Aucun contrat n’est généré pour une proposition non confirmée.', (SELECT COUNT(*) FROM proposition_embauche p JOIN candidature c ON c.id = p.candidature_id JOIN contrat ct ON ct.candidature_id = c.id WHERE p.statut <> 'confirmee') = 0;

-- ============================================================
-- 11. RÉSULTAT
-- ============================================================

-- Normalise les éventuels NULL en échec.
UPDATE tf_resultats SET ok = IF(ok = 1, 1, 0);

SELECT id, libelle AS echec
FROM tf_resultats
WHERE ok = 0
ORDER BY id;

SELECT 'Tous les tests de cohérence SQL sont passés.' AS resultat
WHERE NOT EXISTS (SELECT 1 FROM tf_resultats WHERE ok = 0);

-- Provoque volontairement une erreur SQL (sous-requête scalaire retournant
-- plusieurs lignes) s'il reste au moins un échec. Le client mysql sort alors
-- avec un code non nul.
SELECT (
    SELECT 1 FROM tf_resultats WHERE ok = 0
    UNION ALL
    SELECT 1 FROM tf_resultats WHERE ok = 0
) AS echec_volontaire;

DROP TEMPORARY TABLE IF EXISTS tf_resultats;
