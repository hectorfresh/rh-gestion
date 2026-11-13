<?php

/**
 * Vrai si l'utilisateur connecté possède la permission module.action.
 */
if (! function_exists('can_show')) {
    function can_show(string $module, string $action = 'voir'): bool
    {
        $user = session()->get('user');
        if (! $user) {
            return false;
        }

        $permissions = session()->get('permissions');
        if (! is_array($permissions)) {
            $permissions = (new \App\Services\PermissionService())->charger((int) $user['role_id']);
            session()->set('permissions', $permissions);
        }

        return \App\Services\PermissionService::autorise($permissions, $module, $action);
    }
}

if (! function_exists('permission_portee')) {
    function permission_portee(string $module, string $action = 'voir'): string
    {
        $permissions = session()->get('permissions') ?? [];
        return \App\Services\PermissionService::portee($permissions, $module, $action);
    }
}
