-- ==========================================
-- DONNÉES DE BASE
-- Fichier : data.sql
-- ==========================================

USE rh;

-- 1. Rôles
INSERT INTO role (libelle) VALUES 
('dg'), 
('rh'), 
('manager'), 
('candidat');

-- 2. Utilisateurs par défaut
INSERT INTO utilisateur (nom, email, mdp, role_id) VALUES 
('dg', 'dg@mail.com', '1234', 1),
('rh', 'rh@mail.com', '1234', 2),
('manager', 'manager@mail.com', '1234', 3),
('candidat', 'candidat@mail.com', '1234', 4);

-- 3. Statuts des besoins
INSERT INTO statut_besoin (libelle) VALUES 
('Créé'),
('Cloturé'),
('Validé Par RH'),
('Refusé Par RH'),
('Validé Par DG'),
('Refusé Par DG');

-- 4. Postes
INSERT INTO poste (libelle, salaire_base, salaire_max) VALUES 
('Developpeur Junior', 2000, 4000),
('Developpeur Senior', 5000, 7000),
('Commercial', 3000, 4000),
('Comptable', 3000, 4000000000);

-- 5. Budget global
INSERT INTO budget (valeur) VALUES (200000);

-- 6. Langues
INSERT INTO langue (libelle) VALUES 
('Français'),
('Anglais'),
('Malagasy');

-- 7. Niveaux de langue
INSERT INTO niveau (libelle) VALUES 
('Débutant'),
('Intermédiaire'),
('Avancé'),
('Courant');

-- 8. Statuts des candidatures
INSERT INTO statut_candidature (libelle) VALUES 
('En attente'),
('Accepté'),
('Refusé'),
('En attente entretien'),
('Embauché'),
('Non retenu');

-- 9. Statuts des entretiens
INSERT INTO statut_entretien (libelle) VALUES 
('Proposé'),
('Accepté'),
('Refusé');



-- Types de congé
INSERT INTO type_conge (libelle, jours_max_par_an, justificatif_requis, couleur) VALUES
('Congé payé',        30,  0, '#3498db'),
('Congé maladie',     15,  1, '#e74c3c'),
('Congé sans solde',  NULL, 0, '#95a5a6'),
('Congé maternité',   112, 1, '#9b59b6'),
('Congé paternité',   11,  1, '#2ecc71');

-- Statuts de demande
INSERT INTO statut_conge (libelle) VALUES
('En attente'),
('Accepté'),
('Refusé'),
('Annulé');

-- Soldes initiaux pour l'année en cours (ex: 2026)
-- On donne 30 jours de congé payé à tous les utilisateurs
INSERT INTO solde_conge (utilisateur_id, type_conge_id, annee, jours_acquis, jours_pris)
SELECT u.id, 
       (SELECT id FROM type_conge WHERE libelle='Congé payé' LIMIT 1), 
       2026, 
       30, 
       0
FROM utilisateur u;