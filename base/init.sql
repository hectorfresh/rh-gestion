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

CREATE TABLE candidature(
    id INT PRIMARY KEY AUTO_INCREMENT, 
    annonce_id INT, 
    utilisateur_id INT, 
    date_candidature DATETIME, 
    FOREIGN KEY (annonce_id) REFERENCES annonce(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id)
);

CREATE TABLE cv(
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT,
    dilplome_id INT,
    experience_id INT,
    FOREIGN KEY (experience_id) REFERENCES experience(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id),
    FOREIGN KEY (dilplome_id) REFERENCES diplome(id)
);

CREATE TABLE experience(
    id INT PRIMARY KEY AUTO_INCREMENT,
    descri TEXT,
    etablissement VARCHAR(50),  /*ou entreprise*/
    DATE date_debut,
    DATE date_fin
    
);
