<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Contrat extends BaseController
{
    /**
     * Vérifie que l'utilisateur connecté est RH ou DG.
     * Retourne null si OK, sinon un redirect.
     */
    private function checkAccess()
    {
        $user = session()->get('user');
        if ($user == null) {
            return redirect()->to(base_url('login'));
        }

        $roleModel = new \App\Models\RoleModel();
        $rh = $roleModel->where('libelle', 'rh')->first()['id'] ?? null;
        $dg = $roleModel->where('libelle', 'dg')->first()['id'] ?? null;

        if ($user['role_id'] != $rh && $user['role_id'] != $dg) {
            return redirect()->to(base_url('/'))->with('error', 'Accès refusé.');
        }
        return null;
    }

    /**
     * Liste des candidats embauchés -> générer / modifier / télécharger un contrat
     */
    public function index()
    {
        if ($redir = $this->checkAccess()) return $redir;

        $db = \Config\Database::connect();
        $builder = $db->table('candidature');
        $builder->select('candidature.id,
                          utilisateur.nom,
                          utilisateur.email,
                          poste.libelle AS poste,
                          poste.salaire_base,
                          poste.salaire_max,
                          contrat.id AS contrat_id,
                          contrat.salaire_negocie');
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

    /**
     * Formulaire de création / édition du contrat
     */
    public function formulaire($candidature_id)
    {
        if ($redir = $this->checkAccess()) return $redir;

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

        // Contrat existant (pour mode édition)
        $data['contrat'] = $db->table('contrat')
                               ->where('candidature_id', (int) $candidature_id)
                               ->get()->getRowArray();

        return view('form_contrat', $data);
    }

    /**
     * Enregistre le contrat (création ou mise à jour)
     */
    public function save($candidature_id)
    {
        if ($redir = $this->checkAccess()) return $redir;

        $candidature_id = (int) $candidature_id;

        $type_contrat    = trim((string) $this->request->getPost('type_contrat'));
        $date_debut      = $this->request->getPost('date_debut');
        $date_fin        = $this->request->getPost('date_fin') ?: null;
        $salaire_propose = (float) $this->request->getPost('salaire_propose');
        $salaire_negocie = (float) $this->request->getPost('salaire_negocie');
        $heures_travail  = (int) $this->request->getPost('heures_travail');
        $teletravail     = $this->request->getPost('teletravail') === 'oui' ? 1 : 0;
        $periode_essai   = $this->request->getPost('periode_essai') === 'oui' ? 1 : 0;
        $heures_sup      = $this->request->getPost('heures_sup') === 'oui' ? 1 : 0;
        $mutuelle        = $this->request->getPost('mutuelle') === 'oui' ? 1 : 0;
        $avantages       = trim((string) $this->request->getPost('avantages'));

// Validations de base
if ($type_contrat === '' || $date_debut === '' || $salaire_negocie <= 0) {
    return redirect()->back()->withInput()->with('error',
        'Type, date de début et salaire négocié sont obligatoires.');
}

if (!in_array($type_contrat, ['Essai', 'CDD', 'CDI'], true)) {
    return redirect()->back()->withInput()->with('error', 'Type de contrat invalide.');
}

// Date de fin : obligatoire pour Essai/CDD, interdite pour CDI
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

        $db = \Config\Database::connect();

        // Récupération des bornes salariales du poste
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

        // Contrainte budget : le salaire négocié ne doit pas dépasser le salaire max du poste
        if ($salaire_negocie > (float) $info['salaire_max']) {
            return redirect()->back()->withInput()->with(
                'error',
                'Le salaire négocié (' . number_format($salaire_negocie, 2) . ') dépasse le plafond autorisé pour ce poste ('
                . number_format($info['salaire_max'], 2) . ').'
            );
        }
        if ($salaire_negocie < (float) $info['salaire_base'] * 0.5) {
            return redirect()->back()->withInput()->with(
                'error',
                'Le salaire négocié est trop bas par rapport au salaire de base (' . number_format($info['salaire_base'], 2) . ').'
            );
        }

        $data = [
            'candidature_id'  => $candidature_id,
            'type_contrat'    => $type_contrat,
            'date_debut'      => $date_debut,
            'date_fin'        => ($type_contrat === 'CDI' || $type_contrat === 'Stage') ? $date_fin : $date_fin,
            'salaire_propose' => $salaire_propose,
            'salaire_negocie' => $salaire_negocie,
            'heures_travail'  => $heures_travail,
            'teletravail'     => $teletravail,
            'periode_essai'   => $periode_essai,
            'heures_sup'      => $heures_sup,
            'mutuelle'        => $mutuelle,
            'avantages'       => $avantages,
            'date_creation'   => date('Y-m-d H:i:s'),
        ];

        $existing = $db->table('contrat')->where('candidature_id', $candidature_id)->get()->getRowArray();

        if ($existing) {
            $db->table('contrat')->where('id', $existing['id'])->update($data);
            $contrat_id = $existing['id'];
        } else {
            $db->table('contrat')->insert($data);
            $contrat_id = $db->insertID();
        }

        return redirect()->to(base_url('contrats'))->with(
            'success',
            'Contrat enregistré. Vous pouvez maintenant le télécharger en PDF.'
        );
    }

    /**
     * Génère le PDF du contrat
     */
    public function pdf($contrat_id)
    {
        if ($redir = $this->checkAccess()) return $redir;

        $db = \Config\Database::connect();

        $builder = $db->table('contrat');
        $builder->select('contrat.*,
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
            return redirect()->to(base_url('contrats'))->with('error', 'Contrat introuvable.');
        }

        $html = view('contrat_pdf', $data);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Téléchargement direct
        $dompdf->stream('contrat_' . $contrat_id . '.pdf', ['Attachment' => true]);
        exit;
    }
}