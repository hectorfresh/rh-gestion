-- ============================================================
-- SIRH « TalentFlow » — Données de démonstration
-- Fichier : base/data.sql
-- Mot de passe de démonstration : 1234 (password_hash)
-- ============================================================

USE rh;

-- ============================================================
-- 1. RÔLES ET PERMISSIONS
-- ============================================================

INSERT INTO role (id, libelle, description) VALUES
(1, 'dg',      'Direction générale — hérite des droits RH et manager'),
(2, 'rh',      'Ressources humaines'),
(3, 'manager', 'Manager d’équipe'),
(4, 'employe', 'Salarié'),
(5, 'candidat','Candidat externe');

INSERT INTO permission_module (code, libelle) VALUES
('dashboard',   'Tableau de bord'),
('annonces',    'Annonces'),
('besoins',     'Besoins de recrutement'),
('candidatures','Candidatures'),
('entretiens',  'Entretiens'),
('propositions','Propositions d’embauche'),
('contrats',    'Contrats'),
('salaries',    'Salariés'),
('dossiers',    'Dossiers administratifs'),
('documents',   'Documents / GED'),
('pointages',   'Pointages'),
('planning',    'Planning'),
('absences',    'Absences'),
('conges',      'Congés'),
('paie',        'Paie variable'),
('rapports',    'Rapports'),
('admin',       'Administration'),
('audit',       'Journal d’audit'),
('notifications','Notifications');

INSERT INTO permission_action (code, libelle) VALUES
('voir',     'Consulter'),
('creer',    'Créer'),
('modifier', 'Modifier'),
('valider',  'Valider'),
('refuser',  'Refuser'),
('annuler',  'Annuler'),
('repondre', 'Répondre'),
('evaluer',  'Évaluer'),
('supprimer','Supprimer'),
('exporter', 'Exporter');

-- DG : tous les droits, portée globale.
INSERT INTO role_permission (role_id, module, action, portee) VALUES
(1, 'dashboard',    'voir',      'tout'),
(1, 'annonces',     'voir',      'tout'),
(1, 'annonces',     'creer',     'tout'),
(1, 'annonces',     'modifier',  'tout'),
(1, 'besoins',      'voir',      'tout'),
(1, 'besoins',      'creer',     'tout'),
(1, 'besoins',      'valider',   'tout'),
(1, 'candidatures', 'voir',      'tout'),
(1, 'candidatures', 'creer',     'tout'),
(1, 'candidatures', 'modifier',  'tout'),
(1, 'entretiens',   'voir',      'tout'),
(1, 'entretiens',   'creer',     'tout'),
(1, 'entretiens',   'evaluer',   'tout'),
(1, 'propositions', 'voir',      'tout'),
(1, 'propositions', 'creer',     'tout'),
(1, 'propositions', 'valider',   'tout'),
(1, 'propositions', 'repondre',  'tout'),
(1, 'contrats',     'voir',      'tout'),
(1, 'contrats',     'modifier',  'tout'),
(1, 'salaries',     'voir',      'tout'),
(1, 'salaries',     'modifier',  'tout'),
(1, 'dossiers',     'voir',      'tout'),
(1, 'documents',    'voir',      'tout'),
(1, 'documents',    'creer',     'tout'),
(1, 'documents',    'supprimer', 'tout'),
(1, 'pointages',    'voir',      'tout'),
(1, 'pointages',    'creer',     'tout'),
(1, 'pointages',    'modifier',  'tout'),
(1, 'planning',     'voir',      'tout'),
(1, 'planning',     'creer',     'tout'),
(1, 'absences',     'voir',      'tout'),
(1, 'absences',     'creer',     'tout'),
(1, 'conges',       'voir',      'tout'),
(1, 'conges',       'creer',     'tout'),
(1, 'conges',       'valider',   'tout'),
(1, 'conges',       'refuser',   'tout'),
(1, 'conges',       'annuler',   'tout'),
(1, 'paie',         'voir',      'tout'),
(1, 'paie',         'creer',     'tout'),
(1, 'paie',         'supprimer', 'tout'),
(1, 'paie',         'exporter',  'tout'),
(1, 'rapports',     'voir',      'tout'),
(1, 'rapports',     'exporter',  'tout'),
(1, 'admin',        'voir',      'tout'),
(1, 'admin',        'creer',     'tout'),
(1, 'audit',        'voir',      'tout'),
(1, 'notifications','voir',      'tout');

-- RH : gestion complète sauf audit.
INSERT INTO role_permission (role_id, module, action, portee) VALUES
(2, 'dashboard',    'voir',      'tout'),
(2, 'annonces',     'voir',      'tout'),
(2, 'annonces',     'creer',     'tout'),
(2, 'annonces',     'modifier',  'tout'),
(2, 'besoins',      'voir',      'tout'),
(2, 'besoins',      'valider',   'tout'),
(2, 'candidatures', 'voir',      'tout'),
(2, 'candidatures', 'modifier',  'tout'),
(2, 'entretiens',   'voir',      'tout'),
(2, 'entretiens',   'creer',     'tout'),
(2, 'entretiens',   'evaluer',   'tout'),
(2, 'propositions', 'voir',      'tout'),
(2, 'propositions', 'creer',     'tout'),
(2, 'propositions', 'valider',   'tout'),
(2, 'contrats',     'voir',      'tout'),
(2, 'contrats',     'modifier',  'tout'),
(2, 'salaries',     'voir',      'tout'),
(2, 'salaries',     'modifier',  'tout'),
(2, 'dossiers',     'voir',      'tout'),
(2, 'documents',    'voir',      'tout'),
(2, 'documents',    'creer',     'tout'),
(2, 'documents',    'supprimer', 'tout'),
(2, 'pointages',    'voir',      'tout'),
(2, 'pointages',    'creer',     'tout'),
(2, 'pointages',    'modifier',  'tout'),
(2, 'planning',     'voir',      'tout'),
(2, 'planning',     'creer',     'tout'),
(2, 'absences',     'voir',      'tout'),
(2, 'absences',     'creer',     'tout'),
(2, 'conges',       'voir',      'tout'),
(2, 'conges',       'creer',     'tout'),
(2, 'conges',       'valider',   'tout'),
(2, 'conges',       'refuser',   'tout'),
(2, 'conges',       'annuler',   'tout'),
(2, 'paie',         'voir',      'tout'),
(2, 'paie',         'creer',     'tout'),
(2, 'paie',         'supprimer', 'tout'),
(2, 'paie',         'exporter',  'tout'),
(2, 'rapports',     'voir',      'tout'),
(2, 'rapports',     'exporter',  'tout'),
(2, 'admin',        'voir',      'tout'),
(2, 'admin',        'creer',     'tout'),
(2, 'notifications','voir',      'tout');

-- Manager : périmètre équipe, plus ses droits personnels.
INSERT INTO role_permission (role_id, module, action, portee) VALUES
(3, 'dashboard',    'voir',      'equipe'),
(3, 'besoins',      'voir',      'equipe'),
(3, 'besoins',      'creer',     'equipe'),
(3, 'candidatures', 'voir',      'equipe'),
(3, 'entretiens',   'voir',      'equipe'),
(3, 'entretiens',   'creer',     'equipe'),
(3, 'entretiens',   'evaluer',   'equipe'),
(3, 'propositions', 'voir',      'equipe'),
(3, 'contrats',     'voir',      'equipe'),
(3, 'salaries',     'voir',      'equipe'),
(3, 'pointages',    'voir',      'equipe'),
(3, 'pointages',    'creer',     'soi'),
(3, 'planning',     'voir',      'equipe'),
(3, 'planning',     'creer',     'equipe'),
(3, 'absences',     'voir',      'equipe'),
(3, 'absences',     'creer',     'equipe'),
(3, 'conges',       'voir',      'equipe'),
(3, 'conges',       'valider',   'equipe'),
(3, 'conges',       'refuser',   'equipe'),
(3, 'conges',       'creer',     'soi'),
(3, 'conges',       'annuler',   'soi'),
(3, 'rapports',     'voir',      'equipe'),
(3, 'notifications','voir',      'soi');

-- Employé : uniquement ses données.
INSERT INTO role_permission (role_id, module, action, portee) VALUES
(4, 'dashboard',    'voir',      'soi'),
(4, 'annonces',     'voir',      'tout'),
(4, 'contrats',     'voir',      'soi'),
(4, 'documents',    'voir',      'soi'),
(4, 'pointages',    'voir',      'soi'),
(4, 'pointages',    'creer',     'soi'),
(4, 'planning',     'voir',      'soi'),
(4, 'absences',     'voir',      'soi'),
(4, 'conges',       'voir',      'soi'),
(4, 'conges',       'creer',     'soi'),
(4, 'conges',       'annuler',   'soi'),
(4, 'notifications','voir',      'soi');

-- Candidat : ses candidatures, entretiens et propositions.
INSERT INTO role_permission (role_id, module, action, portee) VALUES
(5, 'dashboard',    'voir',      'soi'),
(5, 'annonces',     'voir',      'tout'),
(5, 'candidatures', 'voir',      'soi'),
(5, 'candidatures', 'creer',     'soi'),
(5, 'entretiens',   'voir',      'soi'),
(5, 'propositions', 'voir',      'soi'),
(5, 'propositions', 'repondre',  'soi'),
(5, 'contrats',     'voir',      'soi'),
(5, 'documents',    'voir',      'soi'),
(5, 'notifications','voir',      'soi');

-- ============================================================
-- 2. DÉPARTEMENTS, POSTES, BUDGETS
-- ============================================================

INSERT INTO departement (id, code, libelle, description) VALUES
(1, 'DIR',  'Direction générale', 'Direction et pilotage de l’entreprise'),
(2, 'RH',   'Ressources humaines', 'Recrutement, paie et administration du personnel'),
(3, 'INFO', 'Informatique', 'Développement, infrastructure et support');

INSERT INTO poste (id, departement_id, libelle, salaire_base, salaire_max) VALUES
(1, 1, 'Directeur général',        3000000, 6000000),
(2, 2, 'Responsable RH',           1500000, 2500000),
(3, 2, 'Assistant RH',              500000,  900000),
(4, 3, 'Manager informatique',     1800000, 3000000),
(5, 3, 'Développeur junior',        400000,  800000),
(6, 3, 'Développeur senior',       1200000, 2000000),
(7, 3, 'Chef de projet',           1500000, 2800000);

INSERT INTO budget_departement (departement_id, annee, montant_annuel) VALUES
(1, 2026, 120000000),
(2, 2026,  60000000),
(3, 2026, 300000000);

-- ============================================================
-- 3. RÉFÉRENTIELS RH
-- ============================================================

INSERT INTO regime_travail (id, libelle, heures_hebdomadaires) VALUES
(1, 'Temps plein',   40),
(2, 'Temps partiel', 20),
(3, 'Mi-temps',      30);

INSERT INTO type_conge (id, libelle, jours_max_par_an, justificatif_requis, couleur) VALUES
(1, 'Congé payé',       30,  0, '#3498db'),
(2, 'Congé maladie',    15,  1, '#e74c3c'),
(3, 'Congé sans solde', NULL, 0, '#95a5a6'),
(4, 'Congé maternité', 112, 1, '#9b59b6'),
(5, 'Congé paternité',  11, 1, '#2ecc71');

INSERT INTO droit_conge (type_conge_id, anciennete_min_mois, jours_par_mois, jours_max_annuel) VALUES
(1, 0, 2.50, 30.00),
(2, 0, 1.25, 15.00),
(3, 0, 0.00, NULL),
(4, 0, 9.33, 112.00),
(5, 0, 0.92, 11.00);

INSERT INTO type_document (id, libelle, obligatoire) VALUES
(1, 'Contrat de travail', 1),
(2, 'Diplôme', 1),
(3, 'Pièce d’identité', 1),
(4, 'RIB', 1),
(5, 'Certificat médical', 0),
(6, 'Attestation', 0),
(7, 'Autre', 0);

INSERT INTO statut_besoin (id, libelle) VALUES
(1, 'En attente RH'),
(2, 'En attente DG'),
(3, 'Validé'),
(4, 'Refusé'),
(5, 'Pourvu'),
(6, 'Clôturé');

INSERT INTO statut_candidature (id, libelle) VALUES
(1, 'Reçue'),
(2, 'Présélectionnée'),
(3, 'En attente entretien'),
(4, 'Retenue'),
(5, 'Non retenue'),
(6, 'Embauchée'),
(7, 'Désistée');

INSERT INTO statut_entretien (id, libelle) VALUES
(1, 'Planifié'),
(2, 'Réalisé'),
(3, 'Annulé'),
(4, 'Absent');

INSERT INTO statut_conge (id, libelle) VALUES
(1, 'En attente'),
(2, 'Validé manager'),
(3, 'Accepté'),
(4, 'Refusé'),
(5, 'Annulé');

INSERT INTO langue (id, libelle) VALUES
(1, 'Français'),
(2, 'Anglais'),
(3, 'Malagasy');

INSERT INTO niveau (id, libelle) VALUES
(1, 'Débutant'),
(2, 'Intermédiaire'),
(3, 'Avancé'),
(4, 'Courant');

-- Jours fériés Madagascar 2026.
INSERT INTO jour_ferie (jour, libelle) VALUES
('2026-01-01', 'Jour de l’an'),
('2026-03-08', 'Journée internationale des femmes'),
('2026-03-29', 'Journée des martyrs'),
('2026-05-01', 'Fête du travail'),
('2026-05-21', 'Ascension'),
('2026-06-17', 'Fête nationale'),
('2026-06-26', 'Indépendance'),
('2026-08-15', 'Assomption'),
('2026-11-01', 'Toussaint'),
('2026-12-25', 'Noël');

-- ============================================================
-- 4. UTILISATEURS (mot de passe : 1234)
-- ============================================================

INSERT INTO utilisateur (id, nom, prenom, email, telephone, mdp, role_id, actif) VALUES
(1, 'Rakoto', 'Jean',  'dg@mail.com',       '0340000001', '$2y$12$18/K1Jqjl9mvUIBfE67zyeVRnGjHep6S7vENjvFPOeK8WvIXcLRAm', 1, 1),
(2, 'Rasoa',  'Marie', 'rh@mail.com',       '0340000002', '$2y$12$18/K1Jqjl9mvUIBfE67zyeVRnGjHep6S7vENjvFPOeK8WvIXcLRAm', 2, 1),
(3, 'Randria','Paul',  'manager@mail.com',  '0340000003', '$2y$12$18/K1Jqjl9mvUIBfE67zyeVRnGjHep6S7vENjvFPOeK8WvIXcLRAm', 3, 1),
(4, 'Rabe',   'Luc',   'employe@mail.com',  '0340000004', '$2y$12$18/K1Jqjl9mvUIBfE67zyeVRnGjHep6S7vENjvFPOeK8WvIXcLRAm', 4, 1),
(5, 'Andria', 'Sarah', 'candidat@mail.com', '0340000005', '$2y$12$18/K1Jqjl9mvUIBfE67zyeVRnGjHep6S7vENjvFPOeK8WvIXcLRAm', 5, 1);

-- ============================================================
-- 5. SALARIÉS, CONTRATS, AFFECTATIONS
-- ============================================================

INSERT INTO salarie (id, utilisateur_id, matricule, nom, prenom, email, telephone, ville, date_embauche, departement_id, poste_id, manager_id, regime_travail_id, actif) VALUES
(1, 1, 'EMP-0001', 'Rakoto', 'Jean',  'dg@mail.com',      '0340000001', 'Antananarivo', '2020-01-15', 1, 1, NULL, 1, 1),
(2, 2, 'EMP-0002', 'Rasoa',  'Marie', 'rh@mail.com',      '0340000002', 'Antananarivo', '2021-03-01', 2, 2, 1,    1, 1),
(3, 3, 'EMP-0003', 'Randria','Paul',  'manager@mail.com', '0340000003', 'Antananarivo', '2022-06-01', 3, 4, 1,    1, 1),
(4, 4, 'EMP-0004', 'Rabe',   'Luc',   'employe@mail.com', '0340000004', 'Antananarivo', '2024-09-01', 3, 5, 3,    1, 1);

INSERT INTO affectation (salarie_id, departement_id, poste_id, manager_id, date_debut, motif) VALUES
(1, 1, 1, NULL, '2020-01-15', 'Affectation initiale'),
(2, 2, 2, 1,    '2021-03-01', 'Affectation initiale'),
(3, 3, 4, 1,    '2022-06-01', 'Affectation initiale'),
(4, 3, 5, 3,    '2024-09-01', 'Affectation initiale');

INSERT INTO contrat (salarie_id, type_contrat, date_debut, date_fin, salaire_mensuel, regime_travail_id, heure_debut, heure_fin, statut, date_reponse) VALUES
(1, 'CDI', '2020-01-15', NULL, 5000000, 1, '08:00:00', '17:00:00', 'Accepte', '2020-01-15 09:00:00'),
(2, 'CDI', '2021-03-01', NULL, 2000000, 1, '08:00:00', '17:00:00', 'Accepte', '2021-03-01 09:00:00'),
(3, 'CDI', '2022-06-01', NULL, 2500000, 1, '08:00:00', '17:00:00', 'Accepte', '2022-06-01 09:00:00'),
(4, 'CDI', '2024-09-01', NULL,  600000, 1, '08:00:00', '17:00:00', 'Accepte', '2024-09-01 09:00:00');

INSERT INTO historique_carriere (salarie_id, type_evenement, date_evenement, nouveau_poste_id, nouveau_departement_id, nouveau_salaire, commentaire, created_by) VALUES
(1, 'embauche', '2020-01-15', 1, 1, 5000000, 'Embauche initiale', 1),
(2, 'embauche', '2021-03-01', 2, 2, 2000000, 'Embauche initiale', 1),
(3, 'embauche', '2022-06-01', 4, 3, 2500000, 'Embauche initiale', 1),
(4, 'embauche', '2024-09-01', 5, 3,  600000, 'Embauche initiale', 3);

-- ============================================================
-- 6. CV DU CANDIDAT
-- ============================================================

INSERT INTO cv (id, utilisateur_id, titre, descri) VALUES
(1, 5, 'CV de Sarah Andria', 'Développeuse web junior, motivée et rigoureuse.');

INSERT INTO cv_identite (cv_id, nom, telephone, adresse, ville, email) VALUES
(1, 'Sarah Andria', '0340000005', 'Lot II A 12', 'Antananarivo', 'candidat@mail.com');

INSERT INTO cv_formation (cv_id, diplome, etablissement, date_debut, date_fin) VALUES
(1, 'Licence en informatique', 'Université d’Antananarivo', '2021-09-01', '2024-07-31');

INSERT INTO cv_experience (cv_id, poste, entreprise, date_debut, date_fin, description) VALUES
(1, 'Stagiaire développeuse', 'TechMada', '2024-02-01', '2024-08-31', 'Développement de modules PHP / MySQL.');

INSERT INTO cv_langue (cv_id, langue_id, niveau_id) VALUES
(1, 1, 3),
(1, 2, 2),
(1, 3, 4);

-- ============================================================
-- 7. SCÉNARIO DE DÉMONSTRATION : BESOIN → ANNONCE → CANDIDATURE
-- ============================================================

INSERT INTO besoin (id, poste_id, departement_id, demandeur_id, nombre, type_besoin, description, motif, salaire_estime, date_souhaitee, statut_id, date_creation, date_cloture) VALUES
(1, 5, 3, 3, 1, 'creation',
 'Renfort sur l’équipe web pour les développements PHP / MySQL.',
 'Croissance de l’activité', 600000, '2026-03-01', 3, '2026-01-10 09:00:00', '2026-01-12 11:00:00'),
(2, 6, 3, 3, 1, 'remplacement',
 'Remplacement d’un développeur senior parti en fin d’année.',
 'Départ d’un collaborateur', 1400000, '2026-04-01', 1, '2026-02-10 09:00:00', NULL),
(3, 5, 3, 3, 1, 'creation',
 'Deuxième renfort junior pour l’équipe web.',
 'Croissance de l’activité', 600000, '2026-05-01', 2, '2026-02-12 09:00:00', NULL);

INSERT INTO historique_statut_besoin (besoin_id, statut_id, utilisateur_id, commentaire, date_changement) VALUES
(1, 1, 3, 'Besoin soumis, en attente de validation RH.', '2026-01-10 09:00:00'),
(1, 2, 2, 'Décision RH : validé. Finance : accepté.', '2026-01-11 10:00:00'),
(1, 3, 1, 'Décision finale DG : Validé', '2026-01-12 11:00:00'),
(2, 1, 3, 'Besoin soumis, en attente de validation RH.', '2026-02-10 09:00:00'),
(3, 1, 3, 'Besoin soumis, en attente de validation RH.', '2026-02-12 09:00:00'),
(3, 2, 2, 'Décision RH : validé. Finance : accepté.', '2026-02-13 10:00:00');

INSERT INTO workflow_validation (objet_type, objet_id, etape, decision, valideur_id, automatique, commentaire, details_json, date_decision) VALUES
('besoin', 1, 'rh', 'accepte', 2, 0, 'Besoin conforme au plan de recrutement.',
 '{"departement_id":3,"annee":2026}', '2026-01-11 10:00:00'),
('besoin', 1, 'finance', 'accepte', NULL, 1, 'Coût compatible avec le budget disponible.',
 '{"departement_id":3,"annee":2026,"budget_annuel":300000000,"masse_salariale":37200000,"engagements":0,"cout":7200000,"budget_disponible":262800000,"reste_apres_cout":255600000,"nombre":1,"salaire_estime":600000,"formule":"nombre × salaire_estime × 12"}', '2026-01-10 09:00:01'),
('besoin', 1, 'dg', 'accepte', 1, 0, 'Recrutement validé.', '{"rh":"accepte","finance":"accepte"}', '2026-01-12 11:00:00'),
('besoin', 2, 'rh', 'en_attente', NULL, 0, 'En attente de la décision RH.', NULL, NULL),
('besoin', 2, 'finance', 'accepte', NULL, 1, 'Coût compatible avec le budget disponible.',
 '{"departement_id":3,"annee":2026,"budget_annuel":300000000,"masse_salariale":37200000,"engagements":7200000,"cout":16800000,"budget_disponible":255600000,"reste_apres_cout":238800000,"nombre":1,"salaire_estime":1400000,"formule":"nombre × salaire_estime × 12"}', '2026-02-10 09:00:01'),
('besoin', 2, 'dg', 'en_attente', NULL, 0, 'En attente de la décision du DG.', NULL, NULL),
('besoin', 3, 'rh', 'accepte', 2, 0, 'Profil junior cohérent avec le besoin.',
 '{"commentaire":"Validé RH"}', '2026-02-13 10:00:00'),
('besoin', 3, 'finance', 'accepte', NULL, 1, 'Coût compatible avec le budget disponible.',
 '{"departement_id":3,"annee":2026,"budget_annuel":300000000,"masse_salariale":37200000,"engagements":7200000,"cout":7200000,"budget_disponible":255600000,"reste_apres_cout":248400000,"nombre":1,"salaire_estime":600000,"formule":"nombre × salaire_estime × 12"}', '2026-02-12 09:00:01'),
('besoin', 3, 'dg', 'en_attente', NULL, 0, 'En attente de la décision du DG.', NULL, NULL);

INSERT INTO annonce (id, besoin_id, createur_id, titre, description, profil, date_publication, date_limite, est_cloture) VALUES
(1, 1, 2, 'Développeur junior PHP / MySQL',
 'Rejoignez l’équipe informatique pour concevoir et maintenir nos applications web.',
 'Bac+2/3 en informatique, bases solides en PHP, MySQL et HTML/CSS.',
 '2026-02-01 09:00:00', '2026-12-31', 0);

INSERT INTO candidature (id, annonce_id, utilisateur_id, cv_id, statut_id, lettre_motivation, date_candidature) VALUES
(1, 1, 5, 1, 3,
 'Je souhaite mettre mes compétences en développement PHP au service de votre équipe.',
 '2026-02-05 14:30:00');

INSERT INTO historique_statut_candidature (candidature_id, statut_id, utilisateur_id, commentaire, date_changement) VALUES
(1, 1, 5, 'Candidature déposée.', '2026-02-05 14:30:00'),
(1, 3, 2, 'Entretien planifié avec le candidat.', '2026-12-08 10:00:00');

INSERT INTO entretien (id, candidature_id, type, date_entretien, lieu, commentaire, statut_id, intervieweur_id, reponse_candidat) VALUES
(1, 1, 'RH', '2026-12-20 09:30:00', 'Salle RH — 2e étage', 'Premier entretien de motivation.', 1, 2, 'en_attente');

INSERT INTO notification (utilisateur_id, message, lien, lu) VALUES
(2, 'Nouvelle candidature #1 reçue.', 'candidatures/1', 0),
(3, 'Votre besoin #1 a été accepté par le DG.', 'besoins/1', 0);

INSERT INTO audit_log (utilisateur_id, action, entite, entite_id, details_json, ip, created_at) VALUES
(3, 'besoin.creer', 'besoin', 1,
 '{"poste_id":5,"departement_id":3,"nombre":1,"type_besoin":"creation","salaire_estime":600000}',
 '127.0.0.1', '2026-01-10 09:00:00'),
(3, 'besoin.soumettre', 'besoin', 1,
 '{"statut":"En attente RH","finance":"accepte"}',
 '127.0.0.1', '2026-01-10 09:00:01'),
(2, 'workflow.rh.accepte', 'besoin', 1,
 '{"finance":"accepte","commentaire":"Besoin conforme au plan de recrutement."}',
 '127.0.0.1', '2026-01-11 10:00:00'),
(1, 'workflow.dg.accepte', 'besoin', 1,
 '{"rh":"accepte","finance":"accepte","commentaire":"Recrutement validé."}',
 '127.0.0.1', '2026-01-12 11:00:00'),
(3, 'besoin.creer', 'besoin', 2,
 '{"poste_id":6,"departement_id":3,"nombre":1,"type_besoin":"remplacement","salaire_estime":1400000}',
 '127.0.0.1', '2026-02-10 09:00:00'),
(3, 'besoin.creer', 'besoin', 3,
 '{"poste_id":5,"departement_id":3,"nombre":1,"type_besoin":"creation","salaire_estime":600000}',
 '127.0.0.1', '2026-02-12 09:00:00'),
(2, 'workflow.rh.accepte', 'besoin', 3,
 '{"finance":"accepte","commentaire":"Profil junior cohérent avec le besoin."}',
 '127.0.0.1', '2026-02-13 10:00:00');
