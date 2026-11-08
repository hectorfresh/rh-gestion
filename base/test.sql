USE rh;

-- Créer un CV de test
INSERT INTO cv (utilisateur_id, titre, descri, created_at) 
VALUES (4, 'CV Test', 'Candidat de test', NOW());
SET @cv_id = LAST_INSERT_ID();

INSERT INTO cv_identite (cv_id, email, telephone, ville) 
VALUES (@cv_id, 'candidat@mail.com', '0340000000', 'Antananarivo');

-- Créer un besoin validé et une annonce
INSERT INTO besoin (poste_id, nombre, desce, statut_id, date_creation) 
VALUES (1, 1, 'Test', 5, NOW());
SET @besoin_id = LAST_INSERT_ID();

INSERT INTO annonce (besoin_id, date_creation, desce, estCloture) 
VALUES (@besoin_id, NOW(), 'Annonce test', 0);
SET @annonce_id = LAST_INSERT_ID();

-- Candidature direct "Embauché" (statut_id 5)
INSERT INTO candidature (annonce_id, cv_id, date_candidature, statut_id) 
VALUES (@annonce_id, @cv_id, NOW(), 5);