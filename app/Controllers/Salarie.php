<?php

namespace App\Controllers;

use App\Models\SalarieModel;
use App\Services\AuditService;
use App\Services\CongeService;
use App\Services\SalarieService;

/**
 * Bloc 5 : dossier administratif du salarié et GED.
 */
class Salarie extends BaseController
{
    private string $dossierUploads = 'uploads/documents/';

    public function index()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager', 'employe'])) {
            return $redirection;
        }

        $db = db_connect();
        $recherche = trim((string) $this->request->getGet('q'));
        $departementId = (int) $this->request->getGet('departement_id');
        $actif = $this->request->getGet('actif');
        $page = max(1, (int) ($this->request->getGet('page') ?: 1));
        $parPage = 20;
        $offset = ($page - 1) * $parPage;

        $builder = $db->table('salarie s')
            ->select('s.*, u.nom, u.prenom, u.email, u.telephone, d.libelle AS departement,
                      p.libelle AS poste, r.libelle AS regime,
                      (SELECT COUNT(*) FROM document doc WHERE doc.salarie_id = s.id) AS nb_documents')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->join('departement d', 'd.id = s.departement_id')
            ->join('poste p', 'p.id = s.poste_id')
            ->join('regime_travail r', 'r.id = s.regime_travail_id')
            ->orderBy('s.actif', 'DESC')
            ->orderBy('u.nom');

        if ($this->role() === 'manager') {
            $monSalarie = $this->salarieCourant();
            if (! $monSalarie) {
                return $this->erreur('Aucune fiche salarié associée à votre compte.', 'dashboard');
            }
            $builder->groupStart()
                ->where('s.manager_id', (int) $monSalarie['id'])
                ->orWhere('s.id', (int) $monSalarie['id'])
                ->groupEnd();
        } elseif ($this->role() === 'employe') {
            $monSalarie = $this->salarieCourant();
            if (! $monSalarie) {
                return $this->erreur('Aucune fiche salarié associée à votre compte.', 'dashboard');
            }
            $builder->where('s.id', (int) $monSalarie['id']);
        }

        if ($recherche !== '') {
            $builder->groupStart()
                ->like('u.nom', $recherche)
                ->orLike('u.prenom', $recherche)
                ->orLike('s.matricule', $recherche)
                ->orLike('u.email', $recherche)
                ->groupEnd();
        }
        if ($departementId > 0) {
            $builder->where('s.departement_id', $departementId);
        }
        if ($actif !== null && $actif !== '') {
            $builder->where('s.actif', (int) $actif === 1 ? 1 : 0);
        }

        $total = (clone $builder)->countAllResults();
        $salaries = $builder->limit($parPage, $offset)->get()->getResultArray();

        return $this->vue('salaries/index', [
            'titre' => 'Salariés',
            'active' => 'salaries',
            'salaries' => $salaries,
            'recherche' => $recherche,
            'departement_id' => $departementId,
            'actif' => $actif,
            'page' => $page,
            'par_page' => $parPage,
            'total' => $total,
            'pages' => (int) ceil($total / $parPage),
            'departements' => db_connect()->table('departement')->orderBy('libelle')->get()->getResultArray(),
        ]);
    }

    public function show($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $salarie = $this->chargeSalarie((int) $id);
        if (! $salarie) {
            $this->journaliserRefus('salarie', (int) $id, 'Salarié introuvable');
            return $this->erreur('Salarié introuvable.', 'salaries');
        }

        if (! $this->peutVoirSalarie($salarie)) {
            $this->journaliserRefus('salarie', (int) $id, 'Accès refusé');
            return $this->erreur('Accès refusé.', 'dashboard');
        }

        $db = db_connect();
        $affectations = $db->table('affectation a')
            ->select('a.*, d.libelle AS departement, p.libelle AS poste')
            ->join('departement d', 'd.id = a.departement_id')
            ->join('poste p', 'p.id = a.poste_id')
            ->where('a.salarie_id', (int) $id)
            ->orderBy('a.date_debut', 'DESC')
            ->get()->getResultArray();

        $contrats = (new \App\Models\ContratModel())->where('salarie_id', (int) $id)->orderBy('date_debut', 'DESC')->findAll();
        $documents = db_connect()->table('document doc')
            ->select('doc.*, td.libelle AS type_document')
            ->join('type_document td', 'td.id = doc.type_document_id')
            ->where('doc.salarie_id', (int) $id)
            ->orderBy('doc.created_at', 'DESC')
            ->get()->getResultArray();
        foreach ($documents as &$document) {
            $document['statut_validite'] = (new SalarieService())->statutDocument($document);
        }
        unset($document);
        $soldes = (new CongeService())->soldes((int) $id, (int) date('Y'));
        $historique = $db->table('historique_carriere hc')
            ->select('hc.*, pa.libelle AS ancien_poste, pn.libelle AS nouveau_poste, da.libelle AS ancien_departement, dn.libelle AS nouveau_departement')
            ->join('poste pa', 'pa.id = hc.ancien_poste_id', 'left')
            ->join('poste pn', 'pn.id = hc.nouveau_poste_id', 'left')
            ->join('departement da', 'da.id = hc.ancien_departement_id', 'left')
            ->join('departement dn', 'dn.id = hc.nouveau_departement_id', 'left')
            ->where('hc.salarie_id', (int) $id)
            ->orderBy('hc.date_evenement', 'DESC')
            ->get()->getResultArray();

        $pointages = $db->table('pointage')
            ->where('salarie_id', (int) $id)
            ->orderBy('jour', 'DESC')
            ->limit(20)
            ->get()->getResultArray();

        return $this->vue('salaries/show', [
            'titre' => 'Fiche salarié — ' . $salarie['prenom'] . ' ' . $salarie['nom'],
            'active' => 'salaries',
            'salarie' => $salarie,
            'affectations' => $affectations,
            'contrats' => $contrats,
            'documents' => $documents,
            'soldes' => $soldes,
            'historique' => $historique,
            'pointages' => $pointages,
            'departements' => $db->table('departement')->orderBy('libelle')->get()->getResultArray(),
            'postes' => $db->table('poste')->where('actif', 1)->orderBy('libelle')->get()->getResultArray(),
            'peut_voir_salaire' => $this->peutVoirSalaire(),
            'peut_modifier' => $this->peutModifierSalarie($salarie),
        ]);
    }

    public function modifier($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $salarie = $this->chargeSalarie((int) $id);
        if (! $salarie) {
            return $this->erreur('Salarié introuvable.', 'salaries');
        }
        if (! $this->peutModifierSalarie($salarie)) {
            $this->journaliserRefus('salarie', (int) $id, 'Modification refusée');
            return $this->erreur('Accès refusé.', 'dashboard');
        }

        $db = db_connect();
        if ($this->request->getMethod() === 'post') {
            $estEmploye = $this->role() === 'employe';
            $donnees = [
                'nom' => $estEmploye ? $salarie['nom'] : trim((string) $this->request->getPost('nom')),
                'prenom' => $estEmploye ? $salarie['prenom'] : trim((string) $this->request->getPost('prenom')),
                'email' => $estEmploye ? $salarie['email'] : trim((string) $this->request->getPost('email')),
                'telephone' => trim((string) $this->request->getPost('telephone')),
                'adresse' => trim((string) $this->request->getPost('adresse')),
                'ville' => trim((string) $this->request->getPost('ville')),
            ];

            if (! $estEmploye && ($donnees['nom'] === '' || $donnees['prenom'] === '' || $donnees['email'] === '')) {
                return $this->erreur('Nom, prénom et email sont obligatoires.', 'salaries/' . $id . '/modifier');
            }

            $db->transBegin();
            try {
                if (! $estEmploye) {
                    (new \App\Models\UtilisateurModel())->update((int) $salarie['utilisateur_id'], [
                        'nom' => $donnees['nom'],
                        'prenom' => $donnees['prenom'],
                        'email' => $donnees['email'],
                        'telephone' => $donnees['telephone'],
                    ]);
                } else {
                    (new \App\Models\UtilisateurModel())->update((int) $salarie['utilisateur_id'], [
                        'telephone' => $donnees['telephone'],
                    ]);
                }
                (new SalarieModel())->update((int) $id, [
                    'nom' => $donnees['nom'],
                    'prenom' => $donnees['prenom'],
                    'email' => $donnees['email'],
                    'telephone' => $donnees['telephone'],
                    'adresse' => $donnees['adresse'],
                    'ville' => $donnees['ville'],
                ]);
                $db->transCommit();
            } catch (\Throwable $e) {
                $db->transRollback();
                return $this->erreur('Erreur lors de la mise à jour.', 'salaries/' . $id . '/modifier');
            }

            (new AuditService())->log((int) $this->utilisateur()['id'], 'salarie.modifier', 'salarie', (int) $id, $donnees);
            return $this->ok('Fiche mise à jour.', 'salaries/' . $id);
        }

        return $this->vue('salaries/modifier', [
            'titre' => 'Modifier la fiche — ' . $salarie['prenom'] . ' ' . $salarie['nom'],
            'active' => 'salaries',
            'salarie' => $salarie,
        ]);
    }

    public function affecter($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $salarie = (new SalarieModel())->find((int) $id);
        if (! $salarie) {
            return $this->erreur('Salarié introuvable.', 'salaries');
        }

        $resultat = (new SalarieService())->muter((int) $id, [
            'departement_id' => $this->request->getPost('departement_id'),
            'poste_id'       => $this->request->getPost('poste_id'),
            'date_debut'     => $this->request->getPost('date_debut') ?: date('Y-m-d'),
            'motif'          => $this->request->getPost('motif'),
        ], $this->utilisateur());

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'salaries/' . $id);
        }

        return $this->ok($resultat['message'], 'salaries/' . $id);
    }

    public function dossiers()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $salaries = $db->table('salarie s')
            ->select('s.*, u.nom, u.prenom, d.libelle AS departement, p.libelle AS poste,
                      (SELECT COUNT(*) FROM document doc WHERE doc.salarie_id = s.id) AS nb_documents')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->join('departement d', 'd.id = s.departement_id')
            ->join('poste p', 'p.id = s.poste_id')
            ->where('s.actif', 1)
            ->orderBy('u.nom')
            ->get()->getResultArray();

        (new SalarieService())->notifierDocumentsExpirants();

        return $this->vue('dossiers/index', [
            'titre' => 'Dossiers administratifs',
            'active' => 'dossiers',
            'salaries' => $salaries,
        ]);
    }

    public function dossier($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $salarie = $this->chargeSalarie((int) $id);
        if (! $salarie) {
            return $this->erreur('Salarié introuvable.', 'dossiers');
        }

        $documents = db_connect()->table('document doc')
            ->select('doc.*, td.libelle AS type_document')
            ->join('type_document td', 'td.id = doc.type_document_id')
            ->where('doc.salarie_id', (int) $id)
            ->orderBy('doc.created_at', 'DESC')
            ->get()->getResultArray();
        foreach ($documents as &$document) {
            $document['statut_validite'] = (new SalarieService())->statutDocument($document);
        }
        unset($document);

        (new SalarieService())->notifierDocumentsExpirants();

        return $this->vue('dossiers/show', [
            'titre' => 'Dossier — ' . $salarie['prenom'] . ' ' . $salarie['nom'],
            'active' => 'dossiers',
            'salarie' => $salarie,
            'documents' => $documents,
            'types' => (new \App\Models\TypeDocumentModel())->where('actif', 1)->orderBy('libelle')->findAll(),
        ]);
    }

    public function upload($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $salarie = (new SalarieModel())->find((int) $id);
        if (! $salarie) {
            return $this->erreur('Salarié introuvable.', 'dossiers');
        }

        $fichier = $this->request->getFile('fichier');
        if (! $fichier || ! $fichier->isValid() || $fichier->hasMoved()) {
            return $this->erreur('Aucun fichier valide reçu.', 'dossiers/' . $id);
        }
        if ($fichier->getSize() > 5 * 1024 * 1024) {
            return $this->erreur('Le fichier dépasse la taille maximale de 5 Mo.', 'dossiers/' . $id);
        }

        $extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        if (! in_array(strtolower($fichier->getExtension()), $extensions, true)) {
            return $this->erreur('Format non autorisé (PDF, JPG, PNG, DOC, DOCX).', 'dossiers/' . $id);
        }

        $nomFichier = $fichier->getRandomName();
        $fichier->move(WRITEPATH . $this->dossierUploads, $nomFichier);

        $typeDocumentId = (int) $this->request->getPost('type_document_id');
        $typeDocument = (new \App\Models\TypeDocumentModel())->find($typeDocumentId);
        if (! $typeDocument) {
            return $this->erreur('Type de document invalide.', 'dossiers/' . $id);
        }

        (new \App\Models\DocumentModel())->insert([
            'salarie_id'       => (int) $id,
            'type_document_id' => $typeDocumentId,
            'nom_original'     => $fichier->getClientName(),
            'nom_fichier'      => $nomFichier,
            'chemin'           => $this->dossierUploads . $nomFichier,
            'mime'             => $fichier->getClientMimeType(),
            'taille'           => $fichier->getSize(),
            'date_emission'    => $this->request->getPost('date_emission') ?: null,
            'date_expiration'  => $this->request->getPost('date_expiration') ?: null,
            'uploaded_by'      => (int) $this->utilisateur()['id'],
        ]);

        (new AuditService())->log((int) $this->utilisateur()['id'], 'document.upload', 'salarie', (int) $id, [
            'fichier' => $fichier->getClientName(),
        ]);

        return $this->ok('Document ajouté au dossier.', 'dossiers/' . $id);
    }

    public function download($documentId)
    {
        $document = (new \App\Models\DocumentModel())->find((int) $documentId);
        if (! $document) {
            $this->journaliserRefus('document', (int) $documentId, 'Document introuvable');
            return $this->erreur('Document introuvable.', 'dossiers');
        }

        $salarie = (new SalarieModel())->find((int) $document['salarie_id']);
        if (! $salarie || ! $this->peutVoirDocument($salarie)) {
            $this->journaliserRefus('document', (int) $documentId, 'Accès refusé');
            return $this->erreur('Accès refusé.', 'dashboard');
        }

        $chemin = WRITEPATH . $document['chemin'];
        if (! is_file($chemin)) {
            return $this->erreur('Fichier absent du stockage.', 'dossiers');
        }

        return $this->response->download($chemin, $document['nom_original']);
    }

    public function supprimerDocument($documentId)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $document = (new \App\Models\DocumentModel())->find((int) $documentId);
        if (! $document) {
            return $this->erreur('Document introuvable.', 'dossiers');
        }

        $salarieId = (int) $document['salarie_id'];
        $chemin = WRITEPATH . $document['chemin'];
        if (is_file($chemin)) {
            unlink($chemin);
        }
        (new \App\Models\DocumentModel())->delete((int) $documentId);
        (new AuditService())->log((int) $this->utilisateur()['id'], 'document.supprimer', 'salarie', $salarieId, [
            'document_id' => (int) $documentId,
        ]);

        return $this->ok('Document supprimé.', 'dossiers/' . $salarieId);
    }

    private function chargeSalarie(int $id): ?array
    {
        $db = db_connect();
        return $db->table('salarie s')
            ->select('s.*, u.nom, u.prenom, u.email, u.telephone, d.libelle AS departement,
                      p.libelle AS poste, r.libelle AS regime, r.heures_hebdomadaires,
                      m.nom AS manager_nom, m.prenom AS manager_prenom')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->join('departement d', 'd.id = s.departement_id')
            ->join('poste p', 'p.id = s.poste_id')
            ->join('regime_travail r', 'r.id = s.regime_travail_id')
            ->join('salarie ms', 'ms.id = s.manager_id', 'left')
            ->join('utilisateur m', 'm.id = ms.utilisateur_id', 'left')
            ->where('s.id', $id)
            ->get()->getRowArray();
    }

    private function peutVoirSalarie(array $salarie): bool
    {
        if ($this->estStaff()) {
            return true;
        }
        if ($this->role() === 'manager') {
            $monSalarie = $this->salarieCourant();
            return $monSalarie && ((int) $salarie['manager_id'] === (int) $monSalarie['id'] || (int) $salarie['id'] === (int) $monSalarie['id']);
        }
        if ($this->role() === 'employe') {
            $monSalarie = $this->salarieCourant();
            return $monSalarie && (int) $salarie['id'] === (int) $monSalarie['id'];
        }

        return false;
    }

    private function peutModifierSalarie(array $salarie): bool
    {
        if ($this->estStaff()) {
            return true;
        }
        if ($this->role() === 'manager') {
            $monSalarie = $this->salarieCourant();
            return $monSalarie && (int) $salarie['manager_id'] === (int) $monSalarie['id'];
        }
        if ($this->role() === 'employe') {
            $monSalarie = $this->salarieCourant();
            return $monSalarie && (int) $salarie['id'] === (int) $monSalarie['id'];
        }

        return false;
    }

    private function peutVoirDocument(array $salarie): bool
    {
        return $this->peutVoirSalarie($salarie);
    }

    private function peutVoirSalaire(): bool
    {
        return $this->estStaff();
    }

    private function journaliserRefus(string $entite, int $entiteId, string $motif): void
    {
        (new AuditService())->log(
            $this->utilisateur() ? (int) $this->utilisateur()['id'] : null,
            'acces.refuse',
            $entite,
            $entiteId,
            ['motif' => $motif, 'role' => $this->role()]
        );
    }
}
