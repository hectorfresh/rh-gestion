<?php
namespace App\Controllers;
use App\Models\CandidatureModel;
use App\Models\AnnonceModel;
use App\Controllers\BaseController;

class Candidature extends BaseController
{
    public function form() : string {
        return view('candidature');        
    }
    
    public function postuler($annonce_id)
    {
        $candidatureModel = new CandidatureModel();
        $annonceModel = new AnnonceModel();

        $annonce = $annonceModel->find($annonce_id);
        if (!$annonce) {
            return redirect()->to('/annonces')->with('error', 'Annonce introuvable.');
        }

        if (!session()->has('user')) {
            return redirect()->to('/login')->with('error', 'Vous devez être connecté pour postuler.');
        }

        $utilisateur_id = session()->get('user')['id'];

        $existingCandidature = $candidatureModel->where('annonce_id', $annonce_id)
                                                ->where('utilisateur_id', $utilisateur_id)
                                                ->first();
        if ($existingCandidature) {
            return redirect()->to('/annonces')->with('error', 'Vous avez déjà postulé à cette annonce.');
        }

        $candidatureData = [
            'annonce_id' => $annonce_id,
            'utilisateur_id' => $utilisateur_id,
            'date_candidature' => date('Y-m-d H:i:s')
        ];

        $candidatureModel->insert($candidatureData);

        return redirect()->to('/annonces')->with('success', 'Votre candidature a été soumise avec succès.');
    }
}