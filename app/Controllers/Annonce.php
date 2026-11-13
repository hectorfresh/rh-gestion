<?php

namespace App\Controllers;

use App\Models\AnnonceModel;
use App\Models\CandidatureModel;
use App\Models\CvModel;
use App\Models\LangueModel;
use App\Models\NiveauModel;
use App\Services\RecrutementService;

/**
 * Bloc 2 : annonces publiées à partir des besoins validés.
 *
 * Le manager ne voit ni ne crée d'annonces. La gestion est réservée à RH/DG.
 */
class Annonce extends BaseController
{
    public function index()
    {
        if ($this->role() === 'manager') {
            return $this->erreur('Les annonces ne sont pas accessibles aux managers.', 'dashboard');
        }

        $db = db_connect();
        $annonces = $db->table('annonce a')
            ->select('a.*, b.nombre, b.salaire_estime, p.libelle AS poste, p.salaire_base, p.salaire_max, d.libelle AS departement,
                      (SELECT COUNT(*) FROM candidature c WHERE c.annonce_id = a.id) AS nb_candidatures')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->where('a.est_cloture', 0)
            ->groupStart()
                ->where('a.date_limite IS NULL', null, false)
                ->orWhere('a.date_limite >=', date('Y-m-d'))
            ->groupEnd()
            ->orderBy('a.date_publication', 'DESC')
            ->get()->getResultArray();

        return $this->vue('annonces/index', [
            'titre'    => 'Annonces',
            'active'   => 'annonces',
            'annonces' => $annonces,
        ]);
    }

    public function admin()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $annonces = $db->table('annonce a')
            ->select('a.*, p.libelle AS poste, d.libelle AS departement, b.nombre, b.id AS besoin_id,
                      (SELECT COUNT(*) FROM candidature c WHERE c.annonce_id = a.id) AS nb_candidatures,
                      (SELECT COUNT(*) FROM candidature c
                         JOIN statut_candidature sc ON sc.id = c.statut_id
                        WHERE c.annonce_id = a.id AND sc.libelle IN ("Retenue", "Embauchée")) AS nb_retenues')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->orderBy('a.date_publication', 'DESC')
            ->get()->getResultArray();

        return $this->vue('annonces/admin', [
            'titre'    => 'Gestion des annonces',
            'active'   => 'annonces',
            'annonces' => $annonces,
        ]);
    }

    public function form()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $besoins = $db->table('besoin b')
            ->select('b.id, b.nombre, b.description, b.salaire_estime, b.date_souhaitee,
                      p.libelle AS poste, p.salaire_base, p.salaire_max, d.libelle AS departement')
            ->join('statut_besoin sb', 'sb.id = b.statut_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->where('sb.libelle', 'Validé')
            ->where('b.id NOT IN (SELECT besoin_id FROM annonce)', null, false)
            ->orderBy('b.date_creation', 'DESC')
            ->get()->getResultArray();

        return $this->vue('annonces/form', [
            'titre'    => 'Créer une annonce',
            'active'   => 'annonces',
            'annonce'  => null,
            'besoins'  => $besoins,
            'action'   => base_url('admin/annonces'),
        ]);
    }

    public function store()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $besoinId = (int) $this->request->getPost('besoin_id');
        $resultat = (new RecrutementService())->creerAnnonce($besoinId, $this->donneesAnnonce(), $this->utilisateur());

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'admin/annonces/creer');
        }

        return $this->ok($resultat['message'], 'annonces/' . $resultat['id']);
    }

    public function edit($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $annonce = $db->table('annonce a')
            ->select('a.*, b.nombre, p.libelle AS poste, d.libelle AS departement')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->where('a.id', (int) $id)
            ->get()->getRowArray();

        if (! $annonce) {
            return $this->erreur('Annonce introuvable.', 'admin/annonces');
        }

        return $this->vue('annonces/form', [
            'titre'   => 'Modifier l’annonce #' . $id,
            'active'  => 'annonces',
            'annonce' => $annonce,
            'besoins' => [],
            'action'  => base_url('admin/annonces/' . $id),
        ]);
    }

    public function update($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $resultat = (new RecrutementService())->modifierAnnonce((int) $id, $this->donneesAnnonce(), $this->utilisateur());

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'admin/annonces/' . $id . '/modifier');
        }

        return $this->ok($resultat['message'], 'annonces/' . $id);
    }

    public function show($id)
    {
        if ($this->role() === 'manager') {
            return $this->erreur('Les annonces ne sont pas accessibles aux managers.', 'dashboard');
        }

        $db = db_connect();
        $annonce = $db->table('annonce a')
            ->select('a.*, b.nombre, b.description AS besoin_description, b.salaire_estime, b.date_souhaitee,
                      p.libelle AS poste, p.salaire_base, p.salaire_max, d.libelle AS departement,
                      (SELECT COUNT(*) FROM candidature c WHERE c.annonce_id = a.id) AS nb_candidatures')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->where('a.id', (int) $id)
            ->get()->getRowArray();

        if (! $annonce) {
            return $this->erreur('Annonce introuvable.', 'annonces');
        }

        $maCandidature = null;
        if ($this->utilisateur()) {
            $maCandidature = (new CandidatureModel())
                ->where('annonce_id', (int) $id)
                ->where('utilisateur_id', (int) $this->utilisateur()['id'])
                ->first();
        }

        return $this->vue('annonces/show', [
            'titre'          => $annonce['titre'],
            'active'         => 'annonces',
            'annonce'        => $annonce,
            'ma_candidature' => $maCandidature,
        ]);
    }

    public function postulerForm($id)
    {
        if ($redirection = $this->exigerRoles(['candidat'])) {
            return $redirection;
        }

        $annonce = (new AnnonceModel())->find((int) $id);
        if (! $annonce || (int) $annonce['est_cloture'] === 1) {
            return $this->erreur('Annonce indisponible.', 'annonces');
        }
        if (! empty($annonce['date_limite']) && $annonce['date_limite'] < date('Y-m-d')) {
            return $this->erreur('La date limite de candidature est dépassée.', 'annonces/' . $id);
        }

        $deja = (new CandidatureModel())
            ->where('annonce_id', (int) $id)
            ->where('utilisateur_id', (int) $this->utilisateur()['id'])
            ->first();
        if ($deja) {
            return $this->erreur('Vous avez déjà postulé à cette annonce.', 'annonces/' . $id);
        }

        $db = db_connect();
        $annonceComplete = $db->table('annonce a')
            ->select('a.*, p.libelle AS poste, d.libelle AS departement')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->where('a.id', (int) $id)->get()->getRowArray();

        $cvs = (new CvModel())->where('utilisateur_id', (int) $this->utilisateur()['id'])->findAll();

        return $this->vue('annonces/postuler', [
            'titre'    => 'Postuler',
            'active'   => 'annonces',
            'annonce'  => $annonceComplete,
            'cvs'      => $cvs,
            'langues'  => (new LangueModel())->orderBy('libelle')->findAll(),
            'niveaux'  => (new NiveauModel())->orderBy('id')->findAll(),
        ]);
    }

    public function postuler($id)
    {
        if ($redirection = $this->exigerRoles(['candidat'])) {
            return $redirection;
        }

        $utilisateur = $this->utilisateur();
        $resultat = (new RecrutementService())->postuler((int) $id, $utilisateur, [
            'cv_id'                  => (int) $this->request->getPost('cv_id'),
            'titre_cv'               => $this->request->getPost('titre_cv'),
            'description_cv'         => $this->request->getPost('description_cv'),
            'nom'                    => $this->request->getPost('nom') ?: trim($utilisateur['prenom'] . ' ' . $utilisateur['nom']),
            'email'                  => $this->request->getPost('email') ?: $utilisateur['email'],
            'telephone'              => $this->request->getPost('telephone'),
            'adresse'                => $this->request->getPost('adresse'),
            'ville'                  => $this->request->getPost('ville'),
            'diplome'                => $this->request->getPost('diplome'),
            'etablissement'          => $this->request->getPost('etablissement'),
            'experience_poste'       => $this->request->getPost('experience_poste'),
            'experience_entreprise'  => $this->request->getPost('experience_entreprise'),
            'experience_description' => $this->request->getPost('experience_description'),
            'formations'             => $this->request->getPost('formations'),
            'experiences'            => $this->request->getPost('experiences'),
            'langues'                => $this->request->getPost('langues'),
            'lettre_motivation'      => $this->request->getPost('lettre_motivation'),
        ]);

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'annonces/' . $id . '/postuler');
        }

        return $this->ok($resultat['message'], 'candidatures/mes');
    }

    public function cloturer($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $resultat = (new RecrutementService())->cloturerAnnonce((int) $id, $this->utilisateur());
        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'admin/annonces');
        }

        return $this->ok($resultat['message'], 'admin/annonces');
    }

    private function donneesAnnonce(): array
    {
        return [
            'titre'       => $this->request->getPost('titre'),
            'description' => $this->request->getPost('description'),
            'profil'      => $this->request->getPost('profil'),
            'date_limite' => $this->request->getPost('date_limite'),
        ];
    }
}
