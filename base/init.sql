CREATE DATABASE rh;
USE rh;

CREATE TABLE role(
    id INT PRIMARY KEY AUTO_INCREMENT, 
    libelle VARCHAR(50)
);

CREATE TABLE statut_besoin(
    id INT PRIMARY KEY AUTO_INCREMENT, 
    libelle VARCHAR(50)
);

CREATE TABLE utilisateur(
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50), 
    email VARCHAR(50), 
    mdp VARCHAR(50), 
    role_id INT, 
    FOREIGN KEY (role_id) REFERENCES role(id)
);


CREATE TABLE poste(
    id INT PRIMARY KEY AUTO_INCREMENT, 
    libelle VARCHAR(50), 
    salaire_base DECIMAL(14, 2), 
    salaire_max DECIMAL(14,2)
);

CREATE TABLE besoin(
    id INT PRIMARY KEY AUTO_INCREMENT, 
    poste_id INT, 
    nombre INT, 
    desce TEXT, 
    statut_id INT, 
    date_creation DATETIME, 
    FOREIGN KEY (statut_id) REFERENCES statut_besoin(id),
    FOREIGN KEY (poste_id) REFERENCES poste(id) 
);


CREATE TABLE annonce(
    id INT PRIMARY KEY AUTO_INCREMENT, 
    besoin_id INT, 
    date_creation DATETIME, 
    desce TEXT, 
    estCloture INT,
    FOREIGN KEY (besoin_id) REFERENCES besoin(id)
);


CREATE TABLE historique_statut_besoin(
    id INT PRIMARY KEY AUTO_INCREMENT, 
    besoin_id INT, 
    statut_besoin_id INT,
    date_changement DATETIME,  
    FOREIGN KEY (besoin_id) REFERENCES besoin(id),
    FOREIGN KEY (statut_besoin_id) REFERENCES statut_besoin(id) 
);




CREATE TABLE budget(
    id INT PRIMARY KEY AUTO_INCREMENT, 
    valeur DECIMAL(14,2)
);

-- CREATE TABLE candidature(
--     id INT PRIMARY KEY AUTO_INCREMENT, 
--     annonce_id INT, 
--     utilisateur_id INT, 
--     date_candidature DATETIME, 
--     FOREIGN KEY (annonce_id) REFERENCES annonce(id),
--     FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id)
-- );

-- CREATE TABLE cv(
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     utilisateur_id INT,
--     dilplome_id INT,
--     experience_id INT,
--     FOREIGN KEY (experience_id) REFERENCES experience(id),
--     FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id),
--     FOREIGN KEY (dilplome_id) REFERENCES diplome(id)
-- );

-- CREATE TABLE experience(
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     descri TEXT,
--     etablissement VARCHAR(50),  /*ou entreprise*/
--     DATE date_debut,
--     DATE date_fin
    
-- );

CREATE TABLE cv(
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT,
    titre VARCHAR(50),
    descri TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id)
);



CREATE TABLE cv_identite(
    id INT PRIMARY KEY AUTO_INCREMENT,
    cv_id INT,
    nom VARCHAR(50),
    telephone VARCHAR(20),
    adresse VARCHAR(100),
    ville VARCHAR(50),
    email VARCHAR(50),
    FOREIGN KEY (cv_id) REFERENCES cv(id)
);

CREATE TABLE cv_formation(
    id INT PRIMARY KEY AUTO_INCREMENT,
    cv_id INT,
    diplome VARCHAR(50),
    etablissement VARCHAR(50),
    date_debut DATE,
    date_fin DATE,
    FOREIGN KEY (cv_id) REFERENCES cv(id)
);

CREATE TABLE cv_experience(
    id INT PRIMARY KEY AUTO_INCREMENT,
    cv_id INT,
    poste VARCHAR(50),
    entreprise VARCHAR(50),
    date_debut DATE,
    date_fin DATE,
    FOREIGN KEY (cv_id) REFERENCES cv(id)
);
CREATE TABLE langue(
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50)
);
CREATE TABLE niveau(
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50)
);

CREATE TABLE cv_langue(
    id INT PRIMARY KEY AUTO_INCREMENT,
    cv_id INT,
    langue_id INT,
    niveau_id INT,
    FOREIGN KEY (cv_id) REFERENCES cv(id),
    FOREIGN KEY (langue_id) REFERENCES langue(id),
    FOREIGN KEY (niveau_id) REFERENCES niveau(id)
);
CREATE TABLE statut_candidature(
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50)
);

CREATE TABLE candidature(
    id INT PRIMARY KEY AUTO_INCREMENT,
    annonce_id INT,
    cv_id INT,
    date_candidature DATETIME,
    statut_id INT,
    FOREIGN KEY (statut_id) REFERENCES statut_candidature(id),
    FOREIGN KEY (annonce_id) REFERENCES annonce(id),
    FOREIGN KEY (cv_id) REFERENCES cv(id)
);
CREATE TABLE historique_statut_candidature(
    id INT PRIMARY KEY AUTO_INCREMENT,
    candidature_id INT,
    statut_candidature_id INT,
    date_changement DATETIME,
    FOREIGN KEY (candidature_id) REFERENCES candidature(id),
    FOREIGN KEY (statut_candidature_id) REFERENCES statut_candidature(id)
);
CREATE TABLE statut_entretien(
    id INT PRIMARY KEY AUTO_INCREMENT,
    libelle VARCHAR(50)
);

CREATE TABLE entretien(
    id INT PRIMARY KEY AUTO_INCREMENT,
    candidature_id INT,
    date_entretien DATETIME,
    lieu VARCHAR(100),
    commentaire TEXT,
    statut_id INT,
    FOREIGN KEY (statut_id) REFERENCES statut_entretien(id),
    FOREIGN KEY (candidature_id) REFERENCES candidature(id)
);



