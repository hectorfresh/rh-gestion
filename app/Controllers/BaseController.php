<?php

namespace App\Controllers;

use App\Models\SalarieModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Socle commun des contrôleurs : session, rôles, données partagées.
 * Les contrôleurs restent minces, la logique vit dans app/Services.
 */
abstract class BaseController extends Controller
{
    protected $helpers = ['form', 'url', 'text', 'talentflow', 'permission'];
    protected $session;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->session = service('session');
    }

    protected function utilisateur(): ?array
    {
        return session()->get('user');
    }

    protected function role(): string
    {
        return (string) (session()->get('user')['role'] ?? '');
    }

    protected function estRole(string ...$roles): bool
    {
        return in_array($this->role(), $roles, true);
    }

    protected function exigerConnexion(): ?object
    {
        if (! $this->utilisateur()) {
            return redirect()->to(base_url('login'))->with('error', 'Veuillez vous connecter.');
        }
        return null;
    }

    protected function exigerRoles(array $roles): ?object
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }
        if (! in_array($this->role(), $roles, true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Accès refusé : rôle insuffisant.');
        }
        return null;
    }

    protected function estStaff(): bool
    {
        return $this->estRole('rh', 'dg');
    }

    protected function salarieCourant(): ?array
    {
        $user = $this->utilisateur();
        if (! $user) {
            return null;
        }
        return (new SalarieModel())->where('utilisateur_id', (int) $user['id'])->first();
    }

    /** Prépare les données communes à toutes les vues. */
    protected function vue(string $nom, array $data = []): string
    {
        $user = $this->utilisateur();
        $data['utilisateur'] = $user;
        $data['role']        = $this->role();
        $data['titre']       = $data['titre'] ?? 'TalentFlow';

        if ($user && ! isset($data['anomalies_non_resolues'])) {
            $monSalarie = null;
            if ($this->role() === 'manager' || $this->role() === 'employe') {
                $monSalarie = $this->salarieCourant();
            }
            $data['anomalies_non_resolues'] = (new \App\Services\PointageService())->compterAnomaliesNonResolues(
                $this->role() === 'employe' && $monSalarie ? (int) $monSalarie['id'] : null,
                $this->role(),
                $this->role() === 'manager' && $monSalarie ? (int) $monSalarie['id'] : null
            );
        }

        if ($user && ! isset($data['notifications'])) {
            $data['notifications'] = (new \App\Services\NotificationService())->dernieres((int) $user['id'], 6);
            $data['notifications_non_lues'] = (new \App\Services\NotificationService())->nonLues((int) $user['id']);
        }

        return view($nom, $data);
    }

    protected function ok(string $message, string $url): object
    {
        return redirect()->to(base_url($url))->with('success', $message);
    }

    protected function erreur(string $message, ?string $url = null): object
    {
        $redirection = $url ? redirect()->to(base_url($url)) : redirect()->back();
        return $redirection->withInput()->with('error', $message);
    }
}
