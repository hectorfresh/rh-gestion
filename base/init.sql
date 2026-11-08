-- ==========================================
-- BASE DE DONNÉES : SYSTÈME DE RECRUTEMENT
-- Fichier : init.sql (Structure uniquement)
-- ==========================================

DROP DATABASE IF EXISTS rh;
CREATE DATABASE rh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rh;

-- ------------------------------------------
-- 1. TABLES DE RÉFÉRENCE (Indépendantes)
-- ------------------------------------------

CREATE TABLE role (
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE statut_besoin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE poste (
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL,
    salaire_base DECIMAL(14, 2) NOT NULL,
    salaire_max DECIMAL(14, 2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE budget (
    id INT PRIMARY KEY AUTO_INCREMENT,
    valeur DECIMAL(14, 2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE langue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE niveau (
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE statut_candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE statut_entretien (
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- ------------------------------------------
-- 2. UTILISATEURS
-- ------------------------------------------

CREATE TABLE utilisateur (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    mdp VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES role(id)
) ENGINE=InnoDB;

-- ------------------------------------------
-- 3. BESOINS & ANNONCES
-- ------------------------------------------

CREATE TABLE besoin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poste_id INT NOT NULL,
    nombre INT NOT NULL,
    desce TEXT,
    statut_id INT NOT NULL,
    date_creation DATETIME NOT NULL,
    FOREIGN KEY (statut_id) REFERENCES statut_besoin(id),
    FOREIGN KEY (poste_id) REFERENCES poste(id)
) ENGINE=InnoDB;

CREATE TABLE historique_statut_besoin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    besoin_id INT NOT NULL,
    statut_besoin_id INT NOT NULL,
    date_changement DATETIME NOT NULL,
    FOREIGN KEY (besoin_id) REFERENCES besoin(id),
    FOREIGN KEY (statut_besoin_id) REFERENCES statut_besoin(id)
) ENGINE=InnoDB;

CREATE TABLE annonce (
    id INT PRIMARY KEY AUTO_INCREMENT,
    besoin_id INT NOT NULL,
    date_creation DATETIME NOT NULL,
    desce TEXT,
    estCloture INT DEFAULT 0,
    FOREIGN KEY (besoin_id) REFERENCES besoin(id)
) ENGINE=InnoDB;

-- ------------------------------------------
-- 4. CV & CANDIDATURES
-- ------------------------------------------

CREATE TABLE cv (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    titre VARCHAR(50) NOT NULL,
    descri TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id)
) ENGINE=InnoDB;

CREATE TABLE cv_identite (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cv_id INT NOT NULL,
    nom VARCHAR(50),
    telephone VARCHAR(20),
    adresse VARCHAR(100),
    ville VARCHAR(50),
    email VARCHAR(50),
    FOREIGN KEY (cv_id) REFERENCES cv(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cv_formation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cv_id INT NOT NULL,
    diplome VARCHAR(50),
    etablissement VARCHAR(50),
    date_debut DATE,
    date_fin DATE,
    FOREIGN KEY (cv_id) REFERENCES cv(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cv_experience (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cv_id INT NOT NULL,
    poste VARCHAR(50),
    entreprise VARCHAR(50),
    date_debut DATE,
    date_fin DATE,
    FOREIGN KEY (cv_id) REFERENCES cv(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cv_langue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cv_id INT NOT NULL,
    langue_id INT NOT NULL,
    niveau_id INT NOT NULL,
    FOREIGN KEY (cv_id) REFERENCES cv(id) ON DELETE CASCADE,
    FOREIGN KEY (langue_id) REFERENCES langue(id),
    FOREIGN KEY (niveau_id) REFERENCES niveau(id)
) ENGINE=InnoDB;

CREATE TABLE candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    annonce_id INT NOT NULL,
    cv_id INT NOT NULL,
    date_candidature DATETIME NOT NULL,
    statut_id INT NOT NULL,
    FOREIGN KEY (statut_id) REFERENCES statut_candidature(id),
    FOREIGN KEY (annonce_id) REFERENCES annonce(id),
    FOREIGN KEY (cv_id) REFERENCES cv(id)
) ENGINE=InnoDB;

CREATE TABLE historique_statut_candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    candidature_id INT NOT NULL,
    statut_candidature_id INT NOT NULL,
    date_changement DATETIME NOT NULL,
    FOREIGN KEY (candidature_id) REFERENCES candidature(id),
    FOREIGN KEY (statut_candidature_id) REFERENCES statut_candidature(id)
) ENGINE=InnoDB;

-- ------------------------------------------
-- 5. ENTRETIENS & CONTRATS
-- ------------------------------------------

CREATE TABLE entretien (
    id INT PRIMARY KEY AUTO_INCREMENT,
    candidature_id INT NOT NULL,
    date_entretien DATETIME NOT NULL,
    lieu VARCHAR(100) NOT NULL,
    commentaire TEXT,
    statut_id INT NOT NULL,
    FOREIGN KEY (statut_id) REFERENCES statut_entretien(id),
    FOREIGN KEY (candidature_id) REFERENCES candidature(id)
) ENGINE=InnoDB;

CREATE TABLE contrat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    candidature_id INT NOT NULL UNIQUE,
    type_contrat ENUM('Essai', 'CDD', 'CDI') NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    salaire_negocie DECIMAL(14,2) NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    statut VARCHAR(20) DEFAULT 'Proposé',
    date_creation DATETIME NOT NULL,
    date_reponse DATETIME NULL,
    FOREIGN KEY (candidature_id) REFERENCES candidature(id) ON DELETE CASCADE
) ENGINE=InnoDB;







-- ==========================================
-- MODULE GESTION DES CONGÉS
-- ==========================================

CREATE TABLE type_conge (
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL,
    jours_max_par_an INT NULL,
    justificatif_requis TINYINT DEFAULT 0,
    couleur VARCHAR(7) DEFAULT '#3498db'
) ENGINE=InnoDB;

CREATE TABLE statut_conge (
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE solde_conge (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    type_conge_id INT NOT NULL,
    annee INT NOT NULL,
    jours_acquis DECIMAL(4,1) DEFAULT 0,
    jours_pris DECIMAL(4,1) DEFAULT 0,
    UNIQUE KEY uk_solde (utilisateur_id, type_conge_id, annee),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id),
    FOREIGN KEY (type_conge_id) REFERENCES type_conge(id)
) ENGINE=InnoDB;

CREATE TABLE demande_conge (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    type_conge_id INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    nombre_jours INT NOT NULL,
    motif TEXT,
    justificatif VARCHAR(255) NULL,
    statut_id INT NOT NULL,
    date_demande DATETIME NOT NULL,
    date_traitement DATETIME NULL,
    traite_par INT NULL,
    commentaire_validation TEXT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id),
    FOREIGN KEY (type_conge_id) REFERENCES type_conge(id),
    FOREIGN KEY (statut_id) REFERENCES statut_conge(id),
    FOREIGN KEY (traite_par) REFERENCES utilisateur(id)
) ENGINE=InnoDB;

CREATE TABLE historique_statut_conge (
    id INT PRIMARY KEY AUTO_INCREMENT,
    demande_conge_id INT NOT NULL,
    statut_conge_id INT NOT NULL,
    date_changement DATETIME NOT NULL,
    utilisateur_id INT NULL,
    FOREIGN KEY (demande_conge_id) REFERENCES demande_conge(id),
    FOREIGN KEY (statut_conge_id) REFERENCES statut_conge(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id)
) ENGINE=InnoDB;