INSERT INTO role(libelle) VALUES('dg');
INSERT INTO role(libelle) VALUES('rh');
INSERT INTO role(libelle) VALUES('manager');
INSERT INTO role(libelle) VALUES('candidat');

INSERT INTO utilisateur(nom, email, mdp, role_id) VALUES('dg', 'dg@mail.com', '1234', 1);
INSERT INTO utilisateur(nom, email, mdp, role_id) VALUES('rh', 'rh@mail.com', '1234', 2);
INSERT INTO utilisateur(nom, email, mdp, role_id) VALUES('manager', 'manager@mail.com', '1234', 3);
INSERT INTO utilisateur(nom, email, mdp, role_id) VALUES('candidat', 'candidat@mail.com', '1234', 4);

INSERT INTO statut_besoin(libelle) VALUES('Créé');
INSERT INTO statut_besoin(libelle) VALUES('Cloturé');
INSERT INTO statut_besoin(libelle) VALUES('Validé Par RH');
INSERT INTO statut_besoin(libelle) VALUES('Refusé Par RH');
INSERT INTO statut_besoin(libelle) VALUES('Validé Par DG');
INSERT INTO statut_besoin(libelle) VALUES('Refusé Par DG');

INSERT INTO poste(libelle, salaire_base, salaire_max) VALUES('Developpeur Junior', 2000, 4000);
INSERT INTO poste(libelle, salaire_base, salaire_max) VALUES('Developpeur Senior', 5000, 7000);
INSERT INTO poste(libelle, salaire_base, salaire_max) VALUES('Commercial', 3000, 4000);

INSERT INTO poste(libelle, salaire_base, salaire_max) VALUES('Comptable', 3000, 4000000000);

INSERT INTO budget(valeur) VALUES(200000);

INSERT INTO langue(libelle) VALUES('Français');
INSERT INTO langue(libelle) VALUES('Anglais');
INSERT INTO langue(libelle) VALUES('Malagasy');

INSERT INTO niveau(libelle) VALUES('Débutant');
INSERT INTO niveau(libelle) VALUES('Intermédiaire');
INSERT INTO niveau(libelle) VALUES('Avancé');
INSERT INTO niveau(libelle) VALUES('Courant');

INSERT INTO statut_candidature(libelle) VALUES('En attente');
INSERT INTO statut_candidature(libelle) VALUES('Accepté');
INSERT INTO statut_candidature(libelle) VALUES('Refusé');

INSERT INTO statut_entretien(libelle) VALUES('Proposé');
INSERT INTO statut_entretien(libelle) VALUES('Accepté');
INSERT INTO statut_entretien(libelle) VALUES('Refusé');