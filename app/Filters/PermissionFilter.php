<?php

namespace App\Filters;

use App\Services\PermissionService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Contrôle d'accès centralisé par permission (module.action).
 * Les contrôleurs conservent leurs contrôles métier et de portée.
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $path = trim($request->getUri()->getPath(), '/');

        // Selon la configuration (indexPage), l'URL peut arriver sous la
        // forme « index.php/login ». On normalise pour appliquer les filtres.
        if ($path === 'index.php') {
            $path = '';
        } elseif (str_starts_with($path, 'index.php/')) {
            $path = substr($path, strlen('index.php/'));
        }

        if ($this->estPublique($path)) {
            return null;
        }

        $user = session()->get('user');
        if (! $user) {
            return redirect()->to(base_url('login'))->with('error', 'Veuillez vous connecter.');
        }

        $permissions = session()->get('permissions');
        if (! is_array($permissions)) {
            $permissions = (new PermissionService())->charger((int) $user['role_id']);
            session()->set('permissions', $permissions);
        }

        [$module, $action] = $this->resoudre($request->getMethod(), $path);
        if ($module === null) {
            return null;
        }

        if (! PermissionService::autorise($permissions, $module, $action)) {
            $response = service('response');
            $response->setStatusCode(403);
            $response->setBody(view('errors/html/error_403'));

            return $response;
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function estPublique(string $path): bool
    {
        if ($path === '' || $path === 'login' || $path === 'inscription') {
            return true;
        }
        if (str_starts_with($path, 'api/') || $path === 'api') {
            return true;
        }
        if ($path === 'annonces' || preg_match('#^annonces/\d+$#', $path)) {
            return true;
        }
        if (str_starts_with($path, 'assets/') || $path === 'favicon.ico') {
            return true;
        }

        return false;
    }

    /**
     * @return array{0:?string,1:string}
     */
    private function resoudre(string $method, string $path): array
    {
        $segments = explode('/', $path);
        $top      = $segments[0] ?? '';
        $dernier  = end($segments);
        $post     = strtoupper($method) === 'POST';

        switch ($top) {
            case 'logout':
                return [null, ''];

            case 'dashboard':
                return ['dashboard', 'voir'];

            case 'notifications':
                return ['notifications', 'voir'];

            case 'annonces':
                if ($dernier === 'postuler') {
                    return ['candidatures', 'creer'];
                }
                if ($post && $dernier === 'cloturer') {
                    return ['annonces', 'modifier'];
                }
                return ['annonces', 'voir'];

            case 'besoins':
                if ($post) {
                    if ($dernier === 'decider') {
                        return ['besoins', 'valider'];
                    }
                    if ($dernier === 'annonce') {
                        return ['annonces', 'creer'];
                    }
                    return ['besoins', 'creer'];
                }
                return ['besoins', 'voir'];

            case 'candidatures':
                if ($post) {
                    if ($dernier === 'proposition') {
                        return ['propositions', 'creer'];
                    }
                    if ($dernier === 'entretien') {
                        return ['entretiens', 'creer'];
                    }
                    return ['candidatures', 'modifier'];
                }
                return ['candidatures', 'voir'];

            case 'entretiens':
                if ($post && $dernier === 'evaluer') {
                    return ['entretiens', 'evaluer'];
                }
                return ['entretiens', 'voir'];

            case 'propositions':
                if ($post && in_array($dernier, ['confirmer', 'renoncer'], true)) {
                    return ['propositions', 'repondre'];
                }
                if ($post && $dernier === 'repondre') {
                    return ['propositions', 'repondre'];
                }
                if ($post && $dernier === 'decider') {
                    return ['propositions', 'valider'];
                }
                if ($post && in_array($dernier, ['modifier', 'envoyer'], true)) {
                    return ['propositions', 'creer'];
                }
                return ['propositions', 'voir'];

            case 'contrats':
                if ($post && $dernier === 'rompre') {
                    return ['contrats', 'modifier'];
                }
                return ['contrats', 'voir'];

            case 'salaries':
                if ($post && $dernier === 'affectation') {
                    return ['salaries', 'modifier'];
                }
                return ['salaries', 'voir'];

            case 'dossiers':
                if ($post) {
                    return ['documents', 'creer'];
                }
                return ['dossiers', 'voir'];

            case 'documents':
                if ($post) {
                    return ['documents', 'supprimer'];
                }
                return ['documents', 'voir'];

            case 'pointages':
                if ($post) {
                    if ($dernier === 'entree' || $dernier === 'sortie') {
                        return ['pointages', 'creer'];
                    }
                    return ['pointages', 'modifier'];
                }
                return ['pointages', 'voir'];

            case 'planning':
                if ($post) {
                    return ['planning', 'creer'];
                }
                return ['planning', 'voir'];

            case 'absences':
                if ($post) {
                    return ['absences', 'creer'];
                }
                return ['absences', 'voir'];

            case 'conges':
                if ($post) {
                    if (in_array($dernier, ['manager', 'rh'], true)) {
                        return ['conges', 'valider'];
                    }
                    if ($dernier === 'refuser') {
                        return ['conges', 'refuser'];
                    }
                    if ($dernier === 'annuler') {
                        return ['conges', 'annuler'];
                    }
                    return ['conges', 'creer'];
                }
                return ['conges', 'voir'];

            case 'paie':
                if (strtoupper($method) === 'GET' && $dernier === 'export') {
                    return ['paie', 'exporter'];
                }
                if ($post && $dernier === 'supprimer') {
                    return ['paie', 'supprimer'];
                }
                if ($post) {
                    return ['paie', 'creer'];
                }
                return ['paie', 'voir'];

            case 'rapports':
                if ($dernier === 'export' || str_contains($path, '/export/')) {
                    return ['rapports', 'exporter'];
                }
                return ['rapports', 'voir'];

            case 'admin':
                if (str_starts_with($path, 'admin/annonces')) {
                    if ($path === 'admin/annonces/creer' || ($path === 'admin/annonces' && $post)) {
                        return ['annonces', 'creer'];
                    }
                    if ($dernier === 'modifier' || preg_match('#^admin/annonces/\d+$#', $path)) {
                        return ['annonces', 'modifier'];
                    }
                    return ['annonces', 'voir'];
                }
                return ['admin', $post ? 'creer' : 'voir'];

            case 'audit':
                return ['audit', 'voir'];

            default:
                return [null, ''];
        }
    }
}
