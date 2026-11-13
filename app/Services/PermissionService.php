<?php

namespace App\Services;

use App\Models\RolePermissionModel;

/**
 * Permissions chargées une seule fois à la connexion, puis conservées en session.
 * Portées : tout > equipe > soi.
 */
class PermissionService
{
    /** @return array<string,string> Clé "module.action" => portée */
    public function charger(int $roleId): array
    {
        $rows = (new RolePermissionModel())
            ->where('role_id', $roleId)
            ->where('actif', 1)
            ->findAll();

        $permissions = [];
        foreach ($rows as $row) {
            $permissions[$row['module'] . '.' . $row['action']] = $row['portee'];
        }

        return $permissions;
    }

    /** @param array<string,string> $permissions */
    public static function autorise(array $permissions, string $module, string $action): bool
    {
        return isset($permissions[$module . '.' . $action]);
    }

    /** @param array<string,string> $permissions */
    public static function portee(array $permissions, string $module, string $action): string
    {
        return $permissions[$module . '.' . $action] ?? 'aucun';
    }

    public static function peutVoirTout(array $permissions, string $module, string $action = 'voir'): bool
    {
        return self::portee($permissions, $module, $action) === 'tout';
    }

    public static function peutVoirEquipe(array $permissions, string $module, string $action = 'voir'): bool
    {
        return in_array(self::portee($permissions, $module, $action), ['tout', 'equipe'], true);
    }

    public static function peutVoirSoi(array $permissions, string $module, string $action = 'voir'): bool
    {
        return self::portee($permissions, $module, $action) !== 'aucun';
    }
}
