<?php

namespace App\Services;

use App\Models\NotificationModel;
use App\Models\UtilisateurModel;

/**
 * Notifications internes affichées dans le menu et sur le tableau de bord.
 */
class NotificationService
{
    public function envoyer(int $utilisateurId, string $message, ?string $lien = null): void
    {
        if ($utilisateurId <= 0) {
            return;
        }

        (new NotificationModel())->insert([
            'utilisateur_id' => $utilisateurId,
            'message'        => mb_substr($message, 0, 255),
            'lien'           => $lien,
            'lu'             => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /** Envoie à tous les utilisateurs actifs ayant l'un des rôles indiqués. */
    public function envoyerAuxRoles(array $roles, string $message, ?string $lien = null): void
    {
        $db = db_connect();
        $rows = $db->table('utilisateur u')
            ->select('u.id')
            ->join('role r', 'r.id = u.role_id')
            ->whereIn('r.libelle', $roles)
            ->where('u.actif', 1)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $this->envoyer((int) $row['id'], $message, $lien);
        }
    }

    public function nonLues(int $utilisateurId): int
    {
        return (new NotificationModel())
            ->where('utilisateur_id', $utilisateurId)
            ->where('lu', 0)
            ->countAllResults();
    }

    public function dernieres(int $utilisateurId, int $limite = 8): array
    {
        return (new NotificationModel())
            ->where('utilisateur_id', $utilisateurId)
            ->orderBy('created_at', 'DESC')
            ->findAll($limite);
    }
}
