<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Contrat extends BaseController
{
    private function checkAccessRH()
    {
        $user = session()->get('user');
        if ($user == null) return redirect()->to(base_url('login'));

        $roleModel = new \App\Models\RoleModel();
        $rh = $roleModel->where('libelle', 'rh')->first()['id'] ?? null;
        $dg = $roleModel->where('libelle', 'dg')->first()['id'] ?? null;

        if ($user['role_id'] != $rh && $user['role_id'] != $dg) {
            return redirect()->to(base_url('/'))->with('error', 'Accès refusé.');
        }
        return null;
    }

    private function isStaff()
    {
        $user = session()->get('user');
        $roleModel = new \App\Models\RoleModel();
        $rh = $roleModel->where('libelle', 'rh')->first()['id'] ?? null;
        $dg = $roleModel->where('libelle', 'dg')->first()['id'] ?? null;
        return ($user['role_id'] == $rh || $user['role_id'] == $dg);
    }

    /** Liste RH/DG : candidats embauchés + état du contrat */
    public function index()
    {
        if ($redir = $this->checkAccessRH()) return $redir;

        $db = \Config\Database::connect();
        $builder = $db->table('candidature');
        $builder->select('candidature.id,
                          utilisateur.nom,
                          utilisateur.email,
                          poste.libelle AS poste,
                          poste.salaire_base,
                          poste.salaire_max,
                          contrat.id AS contrat_id,
                          contrat.statut AS contrat_statut,
                          contrat.salaire_negocie,
                          contrat.type_contrat,
                          contrat.date_debut,
                          contrat.date_fin,
                          contrat.heure_debut,
                          contrat.heure_fin');
        $builder->join('cv', 'candidature.cv_id = cv.id');
        $builder->join('utilisateur', 'cv.utilisateur_id = utilisateur.id');
        $builder->join('annonce', 'candidature.annonce_id = annonce.id');
        $builder->join('besoin', 'annonce.besoin_id = besoin.id');
        $builder->join('poste', 'besoin.poste_id = poste.id');
        $builder->join('statut_candidature', 'candidature.statut_id = statut_candidature.id');
        $builder->join('contrat', 'contrat.candidature_id = candidature.id', 'left');
        $builder->where('statut_candidature.libelle', 'Embauché');
        $builder->orderBy('candidature.id', 'DESC');

        $data['candidatures'] = $builder->get()->getResultArray();

        return view('contrats', $data);
    }

    /** Formulaire de proposition de contrat (RH/DG) */
    public function formulaire($candidature_id)
    {
        if ($redir = $this->checkAccessRH()) return $redir;

        $db = \Config\Database::connect();

        $builder = $db->table('candidature');
        $builder->select('candidature.id,
                          utilisateur.nom,
                          utilisateur.email,
                          poste.libelle AS poste,
                          poste.salaire_base,
                          poste.salaire_max');
        $builder->join('cv', 'candidature.cv_id = cv.id');
        $builder->join('utilisateur', 'cv.utilisateur_id = utilisateur.id');
        $builder->join('annonce', 'candidature.annonce_id = annonce.id');
        $builder->join('besoin', 'annonce.besoin_id = besoin.id');
        $builder->join('poste', 'besoin.poste_id = poste.id');
        $builder->where('candidature.id', (int) $candidature_id);

        $data['candidature'] = $builder->get()->getRowArray();
        if (!$data['candidature']) {
            return redirect()->to(base_url('contrats'))->with('error', 'Candidature introuvable.');
        }

        $data['contrat'] = $db->table('contrat')
                               ->where('candidature_id', (int) $candidature_id)
                               ->get()->getRowArray();

        return view('form_contrat', $data);
    }

    /** Enregistrer / modifier la proposition de contrat */
    public function save($candidature_id)
    {
        if ($redir = $this->checkAccessRH()) return $redir;

        $candidature_id = (int) $candidature_id;

        $type_contrat    = trim((string) $this->request->getPost('type_contrat'));
        $date_debut      = $this->request->getPost('date_debut');
        $date_fin        = $this->request->getPost('date_fin') ?: null;
        $salaire_negocie = (float) $this->request->getPost('salaire_negocie');
        $heure_debut     = $this->request->getPost('heure_debut');
        $heure_fin       = $this->request->getPost('heure_fin');

        // Validations
        if ($type_contrat === '' || $date_debut === '' || $salaire_negocie <= 0
            || $heure_debut === '' || $heure_fin === '') {
            return redirect()->back()->withInput()->with('error',
                'Tous les champs sont obligatoires.');
        }

        if (!in_array($type_contrat, ['Essai', 'CDD', 'CDI'], true)) {
            return redirect()->back()->withInput()->with('error', 'Type de contrat invalide.');
        }

        if (in_array($type_contrat, ['Essai', 'CDD'], true) && empty($date_fin)) {
            return redirect()->back()->withInput()->with('error',
                'La date de fin est obligatoire pour un contrat d\'essai ou un CDD.');
        }

        if ($type_contrat === 'CDI') {
            $date_fin = null;
        }

        if (!empty($date_fin) && strtotime($date_fin) <= strtotime($date_debut)) {
            return redirect()->back()->withInput()->with('error',
                'La date de fin doit être postérieure à la date de début.');
        }

        if ($heure_fin <= $heure_debut) {
            return redirect()->back()->withInput()->with('error',
                'L\'heure de fin doit être postérieure à l\'heure de début.');
        }

        // Contrainte budget
        $db = \Config\Database::connect();
        $builder = $db->table('candidature');
        $builder->select('poste.salaire_base, poste.salaire_max');
        $builder->join('annonce', 'candidature.annonce_id = annonce.id');
        $builder->join('besoin', 'annonce.besoin_id = besoin.id');
        $builder->join('poste', 'besoin.poste_id = poste.id');
        $builder->where('candidature.id', $candidature_id);
        $info = $builder->get()->getRowArray();

        if (!$info) {
            return redirect()->back()->with('error', 'Candidature introuvable.');
        }

        if ($salaire_negocie > (float) $info['salaire_max']) {
            return redirect()->back()->withInput()->with('error',
                'Le salaire proposé (' . number_format($salaire_negocie, 2)
                . ') dépasse le plafond autorisé pour ce poste ('
                . number_format($info['salaire_max'], 2) . ').');
        }
        if ($salaire_negocie < (float) $info['salaire_base'] * 0.5) {
            return redirect()->back()->withInput()->with('error',
                'Le salaire proposé est trop bas par rapport au salaire de base ('
                . number_format($info['salaire_base'], 2) . ').');
        }

        $data = [
            'candidature_id'  => $candidature_id,
            'type_contrat'    => $type_contrat,
            'date_debut'      => $date_debut,
            'date_fin'        => $date_fin,
            'salaire_negocie' => $salaire_negocie,
            'heure_debut'     => $heure_debut,
            'heure_fin'       => $heure_fin,
            'statut'          => 'Proposé',     // reset à chaque modification
            'date_creation'   => date('Y-m-d H:i:s'),
            'date_reponse'    => null,
        ];

        $existing = $db->table('contrat')->where('candidature_id', $candidature_id)->get()->getRowArray();

        if ($existing) {
            $db->table('contrat')->where('id', $existing['id'])->update($data);
        } else {
            $db->table('contrat')->insert($data);
        }

        return redirect()->to(base_url('contrats'))->with('success',
            'Proposition de contrat envoyée au candidat. En attente de sa réponse.');
    }

    /** Liste candidat : ses contrats proposés / acceptés */
    public function mesContrats()
    {
        $user = session()->get('user');
        if ($user == null) return redirect()->to(base_url('login'));

        $db = \Config\Database::connect();

        $builder = $db->table('contrat');
        $builder->select('contrat.*,
                          poste.libelle AS poste,
                          poste.salaire_base,
                          poste.salaire_max');
        $builder->join('candidature', 'contrat.candidature_id = candidature.id');
        $builder->join('cv', 'candidature.cv_id = cv.id');
        $builder->join('annonce', 'candidature.annonce_id = annonce.id');
        $builder->join('besoin', 'annonce.besoin_id = besoin.id');
        $builder->join('poste', 'besoin.poste_id = poste.id');
        $builder->where('cv.utilisateur_id', $user['id']);
        $builder->orderBy('contrat.date_creation', 'DESC');

        $data['contrats'] = $builder->get()->getResultArray();

        return view('mes_contrats', $data);
    }

    /** Candidat accepte la proposition */
    public function accepter($id)
    {
        $user = session()->get('user');
        if ($user == null) return redirect()->to(base_url('login'));

        $db = \Config\Database::connect();
        $contrat = $this->getContratForCandidate($id);
        if (!$contrat) {
            return redirect()->to(base_url('contrats/mes'))->with('error', 'Contrat introuvable.');
        }
        if ($contrat['statut'] !== 'Proposé') {
            return redirect()->to(base_url('contrats/mes'))->with('error', 'Ce contrat ne peut plus être accepté.');
        }

        $db->table('contrat')
           ->where('id', (int) $id)
           ->update([
               'statut'       => 'Accepté',
               'date_reponse' => date('Y-m-d H:i:s'),
           ]);

        return redirect()->to(base_url('contrats/mes'))->with('success',
            'Félicitations ! Vous avez accepté le contrat. Vous pouvez maintenant télécharger le PDF.');
    }

    /** Candidat refuse la proposition */
    public function refuser($id)
    {
        $user = session()->get('user');
        if ($user == null) return redirect()->to(base_url('login'));

        $db = \Config\Database::connect();
        $contrat = $this->getContratForCandidate($id);
        if (!$contrat) {
            return redirect()->to(base_url('contrats/mes'))->with('error', 'Contrat introuvable.');
        }
        if ($contrat['statut'] !== 'Proposé') {
            return redirect()->to(base_url('contrats/mes'))->with('error', 'Ce contrat ne peut plus être refusé.');
        }

        $db->table('contrat')
           ->where('id', (int) $id)
           ->update([
               'statut'       => 'Refusé',
               'date_reponse' => date('Y-m-d H:i:s'),
           ]);

        return redirect()->to(base_url('contrats/mes'))->with('success',
            'Vous avez refusé la proposition. Les RH seront notifiés.');
    }

    private function getContratForCandidate($contrat_id)
    {
        $user = session()->get('user');
        $db = \Config\Database::connect();

        $builder = $db->table('contrat');
        $builder->select('contrat.*, cv.utilisateur_id AS owner_id');
        $builder->join('candidature', 'contrat.candidature_id = candidature.id');
        $builder->join('cv', 'candidature.cv_id = cv.id');
        $builder->where('contrat.id', (int) $contrat_id);
        $contrat = $builder->get()->getRowArray();

        if (!$contrat || $contrat['owner_id'] != $user['id']) {
            return null;
        }
        return $contrat;
    }

    /** Téléchargement PDF — uniquement si le contrat est ACCEPTÉ */
    public function pdf($contrat_id)
    {
        $user = session()->get('user');
        if ($user == null) return redirect()->to(base_url('login'));

        $db = \Config\Database::connect();
        $builder = $db->table('contrat');
        $builder->select('contrat.*,
                          utilisateur.id AS user_id,
                          utilisateur.nom,
                          utilisateur.email,
                          poste.libelle AS poste,
                          poste.salaire_base,
                          poste.salaire_max,
                          candidature.date_candidature');
        $builder->join('candidature', 'contrat.candidature_id = candidature.id');
        $builder->join('cv', 'candidature.cv_id = cv.id');
        $builder->join('utilisateur', 'cv.utilisateur_id = utilisateur.id');
        $builder->join('annonce', 'candidature.annonce_id = annonce.id');
        $builder->join('besoin', 'annonce.besoin_id = besoin.id');
        $builder->join('poste', 'besoin.poste_id = poste.id');
        $builder->where('contrat.id', (int) $contrat_id);
        $data['contrat'] = $builder->get()->getRowArray();

        if (!$data['contrat']) {
            return redirect()->to(base_url('/'))->with('error', 'Contrat introuvable.');
        }
        if ($data['contrat']['statut'] !== 'Accepté') {
            return redirect()->to(base_url('/'))->with('error',
                'Le contrat n\'a pas encore été accepté par le candidat.');
        }

        // Sécurité : RH/DG ou propriétaire du contrat
        $isStaff = $this->isStaff();
        $isOwner = ($user['id'] == $data['contrat']['user_id']);
        if (!$isStaff && !$isOwner) {
            return redirect()->to(base_url('/'))->with('error', 'Accès refusé.');
        }

        $html = view('contrat_pdf', $data);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('contrat_' . $contrat_id . '.pdf', ['Attachment' => true]);
        exit;
    }
}