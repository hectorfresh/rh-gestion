-- ============================================================
-- SIRH « TalentFlow » — Socle de base de données
-- Fichier : base/init.sql
-- MySQL 5.7+ / 8.0+ / MariaDB 10.6+ — InnoDB / utf8mb4
-- ============================================================

DROP DATABASE IF EXISTS rh;
CREATE DATABASE rh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rh;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. RÉFÉRENTIELS
-- ============================================================

CREATE TABLE role (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_role_libelle (libelle)
) ENGINE=InnoDB;

CREATE TABLE permission_module (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    UNIQUE KEY uk_permission_module_code (code)
) ENGINE=InnoDB;

CREATE TABLE permission_action (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    UNIQUE KEY uk_permission_action_code (code)
) ENGINE=InnoDB;

CREATE TABLE role_permission (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    portee ENUM('tout','equipe','soi') NOT NULL DEFAULT 'soi',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_role_permission (role_id, module, action),
    KEY idx_role_permission_module (module, action),
    CONSTRAINT fk_role_permission_role FOREIGN KEY (role_id)
        REFERENCES role(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE departement (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    description TEXT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_departement_code (code)
) ENGINE=InnoDB;

CREATE TABLE poste (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    departement_id INT UNSIGNED NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    salaire_base DECIMAL(14,2) NOT NULL,
    salaire_max DECIMAL(14,2) NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_poste_departement (departement_id),
    KEY idx_poste_actif (actif),
    CONSTRAINT fk_poste_departement FOREIGN KEY (departement_id)
        REFERENCES departement(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_poste_fourchette CHECK (salaire_max >= salaire_base)
) ENGINE=InnoDB;

CREATE TABLE budget_departement (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    departement_id INT UNSIGNED NOT NULL,
    annee SMALLINT UNSIGNED NOT NULL,
    montant_annuel DECIMAL(14,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_budget_departement_annee (departement_id, annee),
    KEY idx_budget_annee (annee),
    CONSTRAINT fk_budget_departement FOREIGN KEY (departement_id)
        REFERENCES departement(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_budget_positif CHECK (montant_annuel >= 0)
) ENGINE=InnoDB;

CREATE TABLE regime_travail (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL,
    heures_hebdomadaires DECIMAL(5,2) NOT NULL DEFAULT 40,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_regime_travail_libelle (libelle),
    CONSTRAINT chk_regime_heures CHECK (heures_hebdomadaires > 0 AND heures_hebdomadaires <= 60)
) ENGINE=InnoDB;

CREATE TABLE type_conge (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL,
    jours_max_par_an DECIMAL(5,2) NULL,
    justificatif_requis TINYINT(1) NOT NULL DEFAULT 0,
    couleur VARCHAR(7) NOT NULL DEFAULT '#3498db',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_type_conge_libelle (libelle)
) ENGINE=InnoDB;

CREATE TABLE droit_conge (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type_conge_id INT UNSIGNED NOT NULL,
    anciennete_min_mois SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    jours_par_mois DECIMAL(4,2) NOT NULL DEFAULT 0,
    jours_max_annuel DECIMAL(5,2) NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_droit_conge_type_anciennete (type_conge_id, anciennete_min_mois),
    CONSTRAINT fk_droit_conge_type FOREIGN KEY (type_conge_id)
        REFERENCES type_conge(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE type_document (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    obligatoire TINYINT(1) NOT NULL DEFAULT 0,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_type_document_libelle (libelle)
) ENGINE=InnoDB;

CREATE TABLE langue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_langue_libelle (libelle)
) ENGINE=InnoDB;

CREATE TABLE niveau (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_niveau_libelle (libelle)
) ENGINE=InnoDB;

CREATE TABLE jour_ferie (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jour DATE NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    UNIQUE KEY uk_jour_ferie_jour (jour)
) ENGINE=InnoDB;

-- Statuts métier (libellés séparés des tables pour éviter les ENUM accentués)
CREATE TABLE statut_besoin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL,
    UNIQUE KEY uk_statut_besoin_libelle (libelle)
) ENGINE=InnoDB;

CREATE TABLE statut_candidature (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL,
    UNIQUE KEY uk_statut_candidature_libelle (libelle)
) ENGINE=InnoDB;

CREATE TABLE statut_entretien (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL,
    UNIQUE KEY uk_statut_entretien_libelle (libelle)
) ENGINE=InnoDB;

CREATE TABLE statut_conge (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL,
    UNIQUE KEY uk_statut_conge_libelle (libelle)
) ENGINE=InnoDB;

-- ============================================================
-- 2. UTILISATEURS
-- ============================================================

CREATE TABLE utilisateur (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL DEFAULT '',
    email VARCHAR(150) NOT NULL,
    telephone VARCHAR(30) NULL,
    mdp VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    derniere_connexion DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_utilisateur_email (email),
    KEY idx_utilisateur_role (role_id),
    KEY idx_utilisateur_actif (actif),
    CONSTRAINT fk_utilisateur_role FOREIGN KEY (role_id)
        REFERENCES role(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 3. BLOC 1 — BESOIN ET HISTORIQUE
-- ============================================================

CREATE TABLE besoin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    poste_id INT UNSIGNED NOT NULL,
    departement_id INT UNSIGNED NOT NULL,
    demandeur_id INT UNSIGNED NOT NULL,
    nombre SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    type_besoin ENUM('creation','remplacement') NOT NULL DEFAULT 'creation',
    description TEXT NULL,
    motif VARCHAR(255) NULL,
    salaire_estime DECIMAL(14,2) NOT NULL DEFAULT 0,
    date_souhaitee DATE NULL,
    statut_id INT UNSIGNED NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_cloture DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_besoin_poste (poste_id),
    KEY idx_besoin_departement (departement_id),
    KEY idx_besoin_demandeur (demandeur_id),
    KEY idx_besoin_statut (statut_id),
    KEY idx_besoin_date (date_creation),
    CONSTRAINT fk_besoin_poste FOREIGN KEY (poste_id)
        REFERENCES poste(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_besoin_departement FOREIGN KEY (departement_id)
        REFERENCES departement(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_besoin_demandeur FOREIGN KEY (demandeur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_besoin_statut FOREIGN KEY (statut_id)
        REFERENCES statut_besoin(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_besoin_nombre CHECK (nombre > 0),
    CONSTRAINT chk_besoin_salaire CHECK (salaire_estime >= 0)
) ENGINE=InnoDB;

CREATE TABLE historique_statut_besoin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    besoin_id INT UNSIGNED NOT NULL,
    statut_id INT UNSIGNED NOT NULL,
    utilisateur_id INT UNSIGNED NULL,
    commentaire VARCHAR(255) NULL,
    date_changement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_historique_besoin (besoin_id),
    KEY idx_historique_besoin_statut (statut_id),
    CONSTRAINT fk_historique_besoin FOREIGN KEY (besoin_id)
        REFERENCES besoin(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_historique_besoin_statut FOREIGN KEY (statut_id)
        REFERENCES statut_besoin(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_historique_besoin_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 4. WORKFLOW GÉNÉRIQUE (besoin et contre-proposition)
-- ============================================================

CREATE TABLE workflow_validation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    objet_type ENUM('besoin','proposition_element') NOT NULL,
    objet_id INT UNSIGNED NOT NULL,
    etape ENUM('rh','finance','dg') NOT NULL,
    decision ENUM('en_attente','accepte','refuse') NOT NULL DEFAULT 'en_attente',
    valideur_id INT UNSIGNED NULL COMMENT 'NULL pour l etape automatique finance',
    automatique TINYINT(1) NOT NULL DEFAULT 0,
    commentaire TEXT NULL,
    details_json JSON NULL,
    date_decision DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_workflow_objet_etape (objet_type, objet_id, etape),
    KEY idx_workflow_decision (decision),
    KEY idx_workflow_etape (etape),
    KEY idx_workflow_objet (objet_type, objet_id),
    CONSTRAINT fk_workflow_valideur FOREIGN KEY (valideur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 5. BLOC 2 — ANNONCES, CV, CANDIDATURES, ENTRETIENS
-- ============================================================

CREATE TABLE annonce (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    besoin_id INT UNSIGNED NOT NULL,
    createur_id INT UNSIGNED NULL,
    titre VARCHAR(190) NOT NULL,
    description TEXT NULL,
    profil TEXT NULL,
    date_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_limite DATE NULL,
    est_cloture TINYINT(1) NOT NULL DEFAULT 0,
    date_cloture DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_annonce_besoin (besoin_id),
    KEY idx_annonce_createur (createur_id),
    KEY idx_annonce_cloture (est_cloture),
    KEY idx_annonce_publication (date_publication),
    CONSTRAINT fk_annonce_besoin FOREIGN KEY (besoin_id)
        REFERENCES besoin(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_annonce_createur FOREIGN KEY (createur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE cv (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    titre VARCHAR(150) NOT NULL,
    descri TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_cv_utilisateur (utilisateur_id),
    CONSTRAINT fk_cv_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cv_identite (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cv_id INT UNSIGNED NOT NULL,
    nom VARCHAR(100) NULL,
    telephone VARCHAR(30) NULL,
    adresse VARCHAR(255) NULL,
    ville VARCHAR(100) NULL,
    email VARCHAR(150) NULL,
    UNIQUE KEY uk_cv_identite_cv (cv_id),
    CONSTRAINT fk_cv_identite_cv FOREIGN KEY (cv_id)
        REFERENCES cv(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cv_formation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cv_id INT UNSIGNED NOT NULL,
    diplome VARCHAR(150) NULL,
    etablissement VARCHAR(150) NULL,
    date_debut DATE NULL,
    date_fin DATE NULL,
    KEY idx_cv_formation_cv (cv_id),
    CONSTRAINT fk_cv_formation_cv FOREIGN KEY (cv_id)
        REFERENCES cv(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cv_experience (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cv_id INT UNSIGNED NOT NULL,
    poste VARCHAR(150) NULL,
    entreprise VARCHAR(150) NULL,
    date_debut DATE NULL,
    date_fin DATE NULL,
    description TEXT NULL,
    KEY idx_cv_experience_cv (cv_id),
    CONSTRAINT fk_cv_experience_cv FOREIGN KEY (cv_id)
        REFERENCES cv(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cv_langue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cv_id INT UNSIGNED NOT NULL,
    langue_id INT UNSIGNED NOT NULL,
    niveau_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uk_cv_langue (cv_id, langue_id),
    CONSTRAINT fk_cv_langue_cv FOREIGN KEY (cv_id)
        REFERENCES cv(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cv_langue_langue FOREIGN KEY (langue_id)
        REFERENCES langue(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cv_langue_niveau FOREIGN KEY (niveau_id)
        REFERENCES niveau(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE candidature (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    annonce_id INT UNSIGNED NOT NULL,
    utilisateur_id INT UNSIGNED NOT NULL,
    cv_id INT UNSIGNED NOT NULL,
    statut_id INT UNSIGNED NOT NULL,
    lettre_motivation TEXT NULL,
    date_candidature DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_candidature_annonce_utilisateur (annonce_id, utilisateur_id),
    KEY idx_candidature_annonce (annonce_id),
    KEY idx_candidature_utilisateur (utilisateur_id),
    KEY idx_candidature_cv (cv_id),
    KEY idx_candidature_statut (statut_id),
    KEY idx_candidature_date (date_candidature),
    CONSTRAINT fk_candidature_annonce FOREIGN KEY (annonce_id)
        REFERENCES annonce(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_candidature_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_candidature_cv FOREIGN KEY (cv_id)
        REFERENCES cv(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_candidature_statut FOREIGN KEY (statut_id)
        REFERENCES statut_candidature(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE historique_statut_candidature (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidature_id INT UNSIGNED NOT NULL,
    statut_id INT UNSIGNED NOT NULL,
    utilisateur_id INT UNSIGNED NULL,
    commentaire VARCHAR(255) NULL,
    date_changement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_historique_candidature (candidature_id),
    CONSTRAINT fk_historique_candidature FOREIGN KEY (candidature_id)
        REFERENCES candidature(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_historique_candidature_statut FOREIGN KEY (statut_id)
        REFERENCES statut_candidature(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_historique_candidature_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE entretien (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidature_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'RH',
    date_entretien DATETIME NOT NULL,
    lieu VARCHAR(190) NOT NULL,
    commentaire TEXT NULL,
    statut_id INT UNSIGNED NOT NULL,
    intervieweur_id INT UNSIGNED NULL,
    note DECIMAL(4,2) NULL,
    reponse_candidat ENUM('en_attente','accepte','refuse') NOT NULL DEFAULT 'en_attente',
    motif_reponse VARCHAR(255) NULL,
    date_reponse DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_entretien_candidature (candidature_id),
    KEY idx_entretien_date (date_entretien),
    KEY idx_entretien_statut (statut_id),
    CONSTRAINT fk_entretien_candidature FOREIGN KEY (candidature_id)
        REFERENCES candidature(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_entretien_statut FOREIGN KEY (statut_id)
        REFERENCES statut_entretien(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_entretien_intervieweur FOREIGN KEY (intervieweur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_entretien_note CHECK (note IS NULL OR (note >= 0 AND note <= 20))
) ENGINE=InnoDB;

CREATE TABLE evaluation_candidat (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidature_id INT UNSIGNED NOT NULL,
    entretien_id INT UNSIGNED NULL,
    note DECIMAL(4,2) NOT NULL,
    appreciation TEXT NULL,
    decision ENUM('retenu','refuse','reserve') NOT NULL DEFAULT 'reserve',
    evaluateur_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_evaluation_candidature (candidature_id),
    KEY idx_evaluation_entretien (entretien_id),
    CONSTRAINT fk_evaluation_candidature FOREIGN KEY (candidature_id)
        REFERENCES candidature(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_evaluation_entretien FOREIGN KEY (entretien_id)
        REFERENCES entretien(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_evaluation_utilisateur FOREIGN KEY (evaluateur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_evaluation_note CHECK (note >= 0 AND note <= 20)
) ENGINE=InnoDB;

-- ============================================================
-- 6. BLOC 3 — PROPOSITION D'EMBAUCHE ET QUESTIONNAIRE
-- ============================================================

CREATE TABLE proposition_embauche (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidature_id INT UNSIGNED NOT NULL,
    statut ENUM('en_cours','en_attente','confirmable','confirmee','abandonnee') NOT NULL DEFAULT 'en_cours',
    created_by INT UNSIGNED NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_envoi DATETIME NULL,
    date_confirmation DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_proposition_embauche_candidature (candidature_id),
    KEY idx_proposition_embauche_statut (statut),
    KEY idx_proposition_embauche_envoi (date_envoi),
    CONSTRAINT fk_proposition_embauche_candidature FOREIGN KEY (candidature_id)
        REFERENCES candidature(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_proposition_embauche_createur FOREIGN KEY (created_by)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE proposition_element (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proposition_id INT UNSIGNED NOT NULL,
    code VARCHAR(40) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    valeur_proposee VARCHAR(190) NOT NULL,
    reponse ENUM('en_attente','oui','non') NOT NULL DEFAULT 'en_attente',
    contre_valeur VARCHAR(190) NULL,
    motif_candidat VARCHAR(255) NULL,
    statut ENUM('propose','accepte','contre_proposition','contre_validee','contre_refusee') NOT NULL DEFAULT 'propose',
    valeur_finale VARCHAR(190) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_proposition_element_code (proposition_id, code),
    KEY idx_proposition_element_statut (statut),
    CONSTRAINT fk_proposition_element_proposition FOREIGN KEY (proposition_id)
        REFERENCES proposition_embauche(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. BLOC 4/5 — SALARIÉ, CONTRAT, CARRIÈRE, GED, ONBOARDING
-- ============================================================

CREATE TABLE salarie (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    candidature_id INT UNSIGNED NULL,
    matricule VARCHAR(30) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL DEFAULT '',
    email VARCHAR(150) NOT NULL,
    telephone VARCHAR(30) NULL,
    adresse VARCHAR(255) NULL,
    ville VARCHAR(100) NULL,
    date_naissance DATE NULL,
    date_embauche DATE NOT NULL,
    date_sortie DATE NULL,
    departement_id INT UNSIGNED NOT NULL,
    poste_id INT UNSIGNED NOT NULL,
    manager_id INT UNSIGNED NULL,
    regime_travail_id INT UNSIGNED NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_salarie_utilisateur (utilisateur_id),
    UNIQUE KEY uk_salarie_matricule (matricule),
    KEY idx_salarie_candidature (candidature_id),
    KEY idx_salarie_departement (departement_id),
    KEY idx_salarie_poste (poste_id),
    KEY idx_salarie_manager (manager_id),
    KEY idx_salarie_actif (actif),
    CONSTRAINT fk_salarie_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_salarie_candidature FOREIGN KEY (candidature_id)
        REFERENCES candidature(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_salarie_departement FOREIGN KEY (departement_id)
        REFERENCES departement(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_salarie_poste FOREIGN KEY (poste_id)
        REFERENCES poste(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_salarie_manager FOREIGN KEY (manager_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_salarie_regime FOREIGN KEY (regime_travail_id)
        REFERENCES regime_travail(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE contrat (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NULL,
    candidature_id INT UNSIGNED NULL,
    type_contrat ENUM('Essai','CDD','CDI','Stage','Alternance') NOT NULL DEFAULT 'CDI',
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    salaire_mensuel DECIMAL(14,2) NOT NULL,
    regime_travail_id INT UNSIGNED NOT NULL,
    heure_debut TIME NOT NULL DEFAULT '08:00:00',
    heure_fin TIME NOT NULL DEFAULT '17:00:00',
    periode_essai_mois TINYINT UNSIGNED NOT NULL DEFAULT 0,
    statut ENUM('Propose','Accepte','Refuse','Rompu','Termine','Remplacé') NOT NULL DEFAULT 'Propose',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_reponse DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_contrat_candidature (candidature_id),
    KEY idx_contrat_salarie (salarie_id),
    KEY idx_contrat_statut (statut),
    KEY idx_contrat_dates (date_debut, date_fin),
    CONSTRAINT fk_contrat_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_contrat_candidature FOREIGN KEY (candidature_id)
        REFERENCES candidature(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_contrat_regime FOREIGN KEY (regime_travail_id)
        REFERENCES regime_travail(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_contrat_salaire CHECK (salaire_mensuel > 0),
    CONSTRAINT chk_contrat_dates CHECK (date_fin IS NULL OR date_fin >= date_debut)
) ENGINE=InnoDB;

CREATE TABLE affectation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NOT NULL,
    departement_id INT UNSIGNED NOT NULL,
    poste_id INT UNSIGNED NOT NULL,
    manager_id INT UNSIGNED NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    motif VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_affectation_salarie (salarie_id),
    KEY idx_affectation_departement (departement_id),
    KEY idx_affectation_manager (manager_id),
    CONSTRAINT fk_affectation_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_affectation_departement FOREIGN KEY (departement_id)
        REFERENCES departement(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_affectation_poste FOREIGN KEY (poste_id)
        REFERENCES poste(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_affectation_manager FOREIGN KEY (manager_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE onboarding (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NOT NULL,
    contrat_id INT UNSIGNED NULL,
    statut ENUM('a_faire','en_cours','termine') NOT NULL DEFAULT 'a_faire',
    date_debut DATE NULL,
    date_fin DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_onboarding_salarie (salarie_id),
    CONSTRAINT fk_onboarding_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_onboarding_contrat FOREIGN KEY (contrat_id)
        REFERENCES contrat(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE onboarding_tache (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    onboarding_id INT UNSIGNED NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    description TEXT NULL,
    responsable_id INT UNSIGNED NULL,
    date_echeance DATE NULL,
    statut ENUM('a_faire','en_cours','terminee') NOT NULL DEFAULT 'a_faire',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_onboarding_tache_onboarding (onboarding_id),
    CONSTRAINT fk_onboarding_tache_onboarding FOREIGN KEY (onboarding_id)
        REFERENCES onboarding(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_onboarding_tache_responsable FOREIGN KEY (responsable_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE document (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NULL,
    candidature_id INT UNSIGNED NULL,
    type_document_id INT UNSIGNED NOT NULL,
    nom_original VARCHAR(190) NOT NULL,
    nom_fichier VARCHAR(190) NOT NULL,
    chemin VARCHAR(255) NOT NULL,
    mime VARCHAR(100) NULL,
    taille INT UNSIGNED NOT NULL DEFAULT 0,
    date_emission DATE NULL,
    date_expiration DATE NULL,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_document_salarie (salarie_id),
    KEY idx_document_candidature (candidature_id),
    KEY idx_document_type (type_document_id),
    KEY idx_document_expiration (date_expiration),
    CONSTRAINT fk_document_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_document_candidature FOREIGN KEY (candidature_id)
        REFERENCES candidature(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_document_type FOREIGN KEY (type_document_id)
        REFERENCES type_document(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_document_uploaded_by FOREIGN KEY (uploaded_by)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE historique_carriere (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NOT NULL,
    type_evenement ENUM('embauche','affectation','promotion','augmentation','sortie','autre') NOT NULL DEFAULT 'autre',
    date_evenement DATE NOT NULL,
    ancien_poste_id INT UNSIGNED NULL,
    nouveau_poste_id INT UNSIGNED NULL,
    ancien_departement_id INT UNSIGNED NULL,
    nouveau_departement_id INT UNSIGNED NULL,
    ancien_salaire DECIMAL(14,2) NULL,
    nouveau_salaire DECIMAL(14,2) NULL,
    commentaire VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_historique_carriere_salarie (salarie_id),
    KEY idx_historique_carriere_date (date_evenement),
    CONSTRAINT fk_historique_carriere_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_historique_carriere_ancien_poste FOREIGN KEY (ancien_poste_id)
        REFERENCES poste(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_historique_carriere_nouveau_poste FOREIGN KEY (nouveau_poste_id)
        REFERENCES poste(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_historique_carriere_ancien_departement FOREIGN KEY (ancien_departement_id)
        REFERENCES departement(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_historique_carriere_nouveau_departement FOREIGN KEY (nouveau_departement_id)
        REFERENCES departement(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_historique_carriere_utilisateur FOREIGN KEY (created_by)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 8. BLOC 6 — TEMPS DE TRAVAIL, POINTAGE, PAIE VARIABLE
-- ============================================================

CREATE TABLE planning_salarie (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NOT NULL,
    jour DATE NOT NULL,
    heure_debut TIME NULL,
    heure_fin TIME NULL,
    type ENUM('travail','repos','conge','ferie') NOT NULL DEFAULT 'travail',
    commentaire VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_planning_salarie_jour (salarie_id, jour),
    KEY idx_planning_jour (jour),
    CONSTRAINT fk_planning_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pointage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NOT NULL,
    jour DATE NOT NULL,
    heure_entree DATETIME NULL,
    heure_sortie DATETIME NULL,
    minutes_travaillees SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    minutes_sup SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    statut ENUM('present','retard','absent','incomplet') NOT NULL DEFAULT 'incomplet',
    commentaire VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_pointage_salarie_jour (salarie_id, jour),
    KEY idx_pointage_jour (jour),
    KEY idx_pointage_statut (statut),
    CONSTRAINT fk_pointage_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE anomalie_pointage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    pointage_id INT UNSIGNED NULL,
    type ENUM('retard','sortie_anticipee','entree_manquante','sortie_manquante','heures_sup','absence','double_entree','jour_non_travaille','duree_incoherente') NOT NULL,
    description VARCHAR(255) NOT NULL,
    resolue TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_anomalie_salarie_date (salarie_id, date),
    KEY idx_anomalie_pointage (pointage_id),
    KEY idx_anomalie_resolue (resolue),
    CONSTRAINT fk_anomalie_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_anomalie_pointage FOREIGN KEY (pointage_id)
        REFERENCES pointage(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE variable_paie (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NOT NULL,
    annee SMALLINT UNSIGNED NOT NULL,
    mois TINYINT UNSIGNED NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    montant DECIMAL(14,2) NOT NULL,
    type ENUM('prime','retenue','heure_sup','autre') NOT NULL DEFAULT 'prime',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_variable_paie_salarie_mois_libelle (salarie_id, annee, mois, libelle),
    KEY idx_variable_paie_periode (annee, mois),
    CONSTRAINT fk_variable_paie_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_variable_paie_utilisateur FOREIGN KEY (created_by)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_variable_paie_mois CHECK (mois BETWEEN 1 AND 12),
    CONSTRAINT chk_variable_paie_montant CHECK (montant <> 0)
) ENGINE=InnoDB;

-- ============================================================
-- 9. BLOC 7 — CONGÉS ET ABSENCES
-- ============================================================

CREATE TABLE solde_conge (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NOT NULL,
    type_conge_id INT UNSIGNED NOT NULL,
    annee SMALLINT UNSIGNED NOT NULL,
    jours_acquis DECIMAL(5,2) NOT NULL DEFAULT 0,
    jours_pris DECIMAL(5,2) NOT NULL DEFAULT 0,
    dernier_mois_credite TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_solde_conge_salarie_type_annee (salarie_id, type_conge_id, annee),
    KEY idx_solde_conge_annee (annee),
    CONSTRAINT fk_solde_conge_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_solde_conge_type FOREIGN KEY (type_conge_id)
        REFERENCES type_conge(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_solde_conge_mois CHECK (dernier_mois_credite BETWEEN 0 AND 12)
) ENGINE=InnoDB;

CREATE TABLE demande_conge (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NOT NULL,
    type_conge_id INT UNSIGNED NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    nombre_jours DECIMAL(4,1) NOT NULL,
    motif TEXT NULL,
    justificatif VARCHAR(255) NULL,
    statut_id INT UNSIGNED NOT NULL,
    date_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    valide_manager_par INT UNSIGNED NULL,
    date_validation_manager DATETIME NULL,
    traite_par INT UNSIGNED NULL,
    date_traitement DATETIME NULL,
    commentaire_validation TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_demande_conge_salarie (salarie_id),
    KEY idx_demande_conge_statut (statut_id),
    KEY idx_demande_conge_dates (date_debut, date_fin),
    CONSTRAINT fk_demande_conge_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_demande_conge_type FOREIGN KEY (type_conge_id)
        REFERENCES type_conge(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_demande_conge_statut FOREIGN KEY (statut_id)
        REFERENCES statut_conge(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_demande_conge_manager FOREIGN KEY (valide_manager_par)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_demande_conge_traite FOREIGN KEY (traite_par)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_demande_conge_dates CHECK (date_fin >= date_debut),
    CONSTRAINT chk_demande_conge_jours CHECK (nombre_jours > 0)
) ENGINE=InnoDB;

CREATE TABLE historique_statut_conge (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    demande_conge_id INT UNSIGNED NOT NULL,
    statut_id INT UNSIGNED NOT NULL,
    utilisateur_id INT UNSIGNED NULL,
    commentaire VARCHAR(255) NULL,
    date_changement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_historique_conge (demande_conge_id),
    CONSTRAINT fk_historique_conge FOREIGN KEY (demande_conge_id)
        REFERENCES demande_conge(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_historique_conge_statut FOREIGN KEY (statut_id)
        REFERENCES statut_conge(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_historique_conge_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE absence (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salarie_id INT UNSIGNED NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    type ENUM('maladie','injustifiee','autorisee','autre') NOT NULL DEFAULT 'autre',
    motif VARCHAR(255) NULL,
    justificatif VARCHAR(255) NULL,
    justifie TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_absence_salarie (salarie_id),
    KEY idx_absence_dates (date_debut, date_fin),
    CONSTRAINT fk_absence_salarie FOREIGN KEY (salarie_id)
        REFERENCES salarie(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_absence_dates CHECK (date_fin >= date_debut)
) ENGINE=InnoDB;

-- ============================================================
-- 10. AUDIT ET NOTIFICATIONS
-- ============================================================

CREATE TABLE audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entite VARCHAR(80) NOT NULL,
    entite_id INT UNSIGNED NULL,
    details_json JSON NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_utilisateur (utilisateur_id),
    KEY idx_audit_entite (entite, entite_id),
    KEY idx_audit_date (created_at),
    CONSTRAINT fk_audit_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notification (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    message VARCHAR(255) NOT NULL,
    lien VARCHAR(255) NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notification_utilisateur (utilisateur_id),
    KEY idx_notification_lu (lu),
    KEY idx_notification_date (created_at),
    CONSTRAINT fk_notification_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
