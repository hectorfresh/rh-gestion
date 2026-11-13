<?php

namespace App\Services;

use App\Models\AuditLogModel;

/**
 * Journal d'audit : toute action sensible est conservée avec son auteur.
 */
class AuditService
{
    public function log(?int $utilisateurId, string $action, string $entite, ?int $entiteId = null, array $details = []): void
    {
        $model = new AuditLogModel();
        $model->insert([
            'utilisateur_id' => $utilisateurId,
            'action'         => $action,
            'entite'         => $entite,
            'entite_id'      => $entiteId,
            'details_json'   => $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE),
            'ip'             => service('request')->getIPAddress(),
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }
}
