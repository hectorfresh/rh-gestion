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