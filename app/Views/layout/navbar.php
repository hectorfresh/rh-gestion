<?php
$role = $role ?? '';
$utilisateur = $utilisateur ?? null;
$notifications = $notifications ?? [];
$notifications_non_lues = $notifications_non_lues ?? 0;
$active = $active ?? '';
$peut = static fn (string $module, string $action = 'voir'): bool => can_show($module, $action);
$espaceCandidat = $role === 'candidat';
$anomalies = (int) ($anomalies_non_resolues ?? 0);

$creations = [];
if ($role === 'manager') {
    if ($peut('besoins', 'creer')) $creations[] = ['libelle' => 'Nouveau besoin', 'url' => 'besoins/creer', 'icone' => 'fa-plus-circle'];
    if ($peut('conges', 'creer')) $creations[] = ['libelle' => 'Demande de congé', 'url' => 'conges/creer', 'icone' => 'fa-plane'];
}
if ($role === 'rh') {
    if ($peut('annonces', 'creer')) $creations[] = ['libelle' => 'Nouvelle annonce', 'url' => 'admin/annonces/creer', 'icone' => 'fa-bullhorn'];
    if ($peut('salaries', 'creer')) $creations[] = ['libelle' => 'Nouveau salarié', 'url' => 'salaries', 'icone' => 'fa-id-card'];
    if ($peut('contrats', 'modifier')) $creations[] = ['libelle' => 'Nouveau contrat', 'url' => 'contrats/echeance', 'icone' => 'fa-file-text-o'];
    if ($peut('besoins', 'creer')) $creations[] = ['libelle' => 'Nouveau besoin', 'url' => 'besoins/creer', 'icone' => 'fa-plus-circle'];
    if ($peut('conges', 'creer')) $creations[] = ['libelle' => 'Demande de congé', 'url' => 'conges/creer', 'icone' => 'fa-plane'];
}
if ($role === 'dg') {
    if ($peut('besoins', 'creer')) $creations[] = ['libelle' => 'Nouveau besoin', 'url' => 'besoins/creer', 'icone' => 'fa-plus-circle'];
    if ($peut('annonces', 'creer')) $creations[] = ['libelle' => 'Nouvelle annonce', 'url' => 'admin/annonces/creer', 'icone' => 'fa-bullhorn'];
}
if ($role === 'employe') {
    if ($peut('conges', 'creer')) $creations[] = ['libelle' => 'Demande de congé', 'url' => 'conges/creer', 'icone' => 'fa-plane'];
}

$menus = [];
if ($utilisateur) {
    $menus[] = ['groupe' => 'Principal', 'items' => [
        ['libelle' => 'Tableau de bord', 'url' => 'dashboard', 'icone' => 'fa-home', 'actif' => $active === 'dashboard'],
    ]];

    if ($espaceCandidat) {
        $menus[] = ['groupe' => 'Candidat', 'items' => [
            ['libelle' => 'Mes candidatures', 'url' => 'candidatures/mes', 'icone' => 'fa-file-text-o', 'actif' => $active === 'candidatures'],
            ['libelle' => 'Mes propositions', 'url' => 'propositions/mes', 'icone' => 'fa-handshake-o', 'actif' => $active === 'propositions'],
            ['libelle' => 'Mes contrats', 'url' => 'contrats/mes', 'icone' => 'fa-file-text-o', 'actif' => $active === 'contrats'],
            ['libelle' => 'Mes congés', 'url' => 'conges/mes', 'icone' => 'fa-plane', 'actif' => $active === 'conges'],
            ['libelle' => 'Mon pointage', 'url' => 'pointages/mes', 'icone' => 'fa-clock-o', 'actif' => $active === 'pointage'],
        ]];
    }

    $recrutement = [];
    if ($peut('besoins')) $recrutement[] = ['libelle' => 'Besoins', 'url' => 'besoins', 'icone' => 'fa-plus-circle', 'actif' => $active === 'besoins'];
    if ($peut('besoins', 'valider')) $recrutement[] = ['libelle' => 'File RH', 'url' => 'besoins/file-rh', 'icone' => 'fa-inbox', 'actif' => $active === 'besoins' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'file-rh')];
    if ($role === 'dg') $recrutement[] = ['libelle' => 'File DG', 'url' => 'besoins/file-dg', 'icone' => 'fa-gavel', 'actif' => $active === 'besoins' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'file-dg')];
    if (in_array($role, ['rh', 'dg'], true) && $peut('candidatures')) $recrutement[] = ['libelle' => 'Candidatures', 'url' => 'candidatures', 'icone' => 'fa-file-text-o', 'actif' => $active === 'candidatures'];
    if (in_array($role, ['rh', 'dg'], true) && $peut('entretiens')) $recrutement[] = ['libelle' => 'Entretiens', 'url' => 'entretiens', 'icone' => 'fa-calendar', 'actif' => $active === 'entretiens'];
    if (in_array($role, ['rh', 'dg'], true) && $peut('propositions')) {
        $recrutement[] = ['libelle' => 'Propositions', 'url' => 'propositions', 'icone' => 'fa-handshake-o', 'actif' => $active === 'propositions'];
        $recrutement[] = ['libelle' => 'File RH propositions', 'url' => 'propositions/file-rh', 'icone' => 'fa-inbox', 'actif' => $active === 'propositions' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'file-rh')];
    }
    if ($role === 'dg' && $peut('propositions')) $recrutement[] = ['libelle' => 'File DG propositions', 'url' => 'propositions/file-dg', 'icone' => 'fa-gavel', 'actif' => $active === 'propositions' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'file-dg')];
    if ($peut('contrats')) $recrutement[] = ['libelle' => 'Contrats', 'url' => 'contrats', 'icone' => 'fa-file-text-o', 'actif' => $active === 'contrats'];
    if (in_array($role, ['rh', 'dg'], true) && $peut('contrats')) $recrutement[] = ['libelle' => 'Contrats à échéance', 'url' => 'contrats/echeance', 'icone' => 'fa-calendar', 'actif' => $active === 'contrats' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'echeance')];
    if (! empty($recrutement)) $menus[] = ['groupe' => 'Recrutement', 'items' => $recrutement];

    $temps = [];
    if ($peut('conges')) $temps[] = ['libelle' => 'Mes congés', 'url' => 'conges/mes', 'icone' => 'fa-plane', 'actif' => $active === 'conges' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'mes')];
    if ($peut('pointages')) $temps[] = ['libelle' => 'Mon pointage', 'url' => 'pointages/mes', 'icone' => 'fa-clock-o', 'actif' => $active === 'pointage' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'mes')];
    if ($peut('contrats')) $temps[] = ['libelle' => 'Mes contrats', 'url' => 'contrats/mes', 'icone' => 'fa-file-text-o', 'actif' => $active === 'contrats' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'mes')];
    if ($peut('conges', 'valider')) $temps[] = ['libelle' => 'Validation congés', 'url' => 'conges/validation', 'icone' => 'fa-check-square', 'actif' => $active === 'conges' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'validation')];
    if ($peut('pointages', 'modifier')) $temps[] = ['libelle' => 'Anomalies', 'url' => 'pointages/anomalies', 'icone' => 'fa-exclamation-triangle', 'actif' => $active === 'pointage' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'anomalies'), 'compteur' => $anomalies];
    if (! empty($temps)) $menus[] = ['groupe' => 'Temps & congés', 'items' => $temps];

    $personnel = [];
    if ($peut('salaries')) $personnel[] = ['libelle' => 'Salariés', 'url' => 'salaries', 'icone' => 'fa-id-card', 'actif' => $active === 'salaries'];
    if ($peut('dossiers')) $personnel[] = ['libelle' => 'Dossiers & GED', 'url' => 'dossiers', 'icone' => 'fa-folder-open', 'actif' => $active === 'dossiers'];
    if (in_array($role, ['rh', 'dg', 'manager'], true) && $peut('pointages')) $personnel[] = ['libelle' => 'Pointages', 'url' => 'pointages', 'icone' => 'fa-clock-o', 'actif' => $active === 'pointage' && ! str_contains($_SERVER['REQUEST_URI'] ?? '', 'mes') && ! str_contains($_SERVER['REQUEST_URI'] ?? '', 'anomalies')];
    if (in_array($role, ['rh', 'dg', 'manager'], true) && $peut('conges')) $personnel[] = ['libelle' => 'Calendrier congés', 'url' => 'conges/calendrier', 'icone' => 'fa-calendar', 'actif' => $active === 'conges' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'calendrier')];
    if ($peut('paie')) $personnel[] = ['libelle' => 'Paie variable', 'url' => 'paie', 'icone' => 'fa-money', 'actif' => $active === 'paie'];
    if ($peut('rapports')) $personnel[] = ['libelle' => 'Rapports', 'url' => 'rapports', 'icone' => 'fa-bar-chart', 'actif' => $active === 'rapports'];
    if ($peut('admin')) $personnel[] = ['libelle' => 'Administration', 'url' => 'admin', 'icone' => 'fa-cogs', 'actif' => $active === 'admin'];
    if ($peut('admin')) $personnel[] = ['libelle' => 'Budgets', 'url' => 'admin/budgets', 'icone' => 'fa-money', 'actif' => $active === 'admin' && str_contains($_SERVER['REQUEST_URI'] ?? '', 'budgets')];
    if ($peut('audit')) $personnel[] = ['libelle' => 'Journal d’audit', 'url' => 'audit', 'icone' => 'fa-history', 'actif' => $active === 'audit'];
    if (! empty($personnel)) $menus[] = ['groupe' => 'Personnel & admin', 'items' => $personnel];
}
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="app-sidebar-brand">
        <span class="app-logo-square">TF</span>
        <div>
            <strong>Gestion</strong>
            <small>SIRH · v2.0</small>
        </div>
    </div>

    <?php if ($utilisateur): ?>
        <?php if ($creations !== []): ?>
            <div class="app-new-request">
                <button type="button" class="btn btn-primary btn-block" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-plus"></i> Nouvelle demande
                </button>
                <ul class="dropdown-menu app-new-menu">
                    <?php foreach ($creations as $creation): ?>
                        <li>
                            <a href="<?= base_url($creation['url']) ?>">
                                <i class="fa <?= esc($creation['icone']) ?>"></i> <?= esc($creation['libelle']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php foreach ($menus as $menu): ?>
            <div class="app-menu-group">
                <span class="app-menu-group-title"><?= esc($menu['groupe']) ?></span>
                <ul class="app-menu-list">
                    <?php foreach ($menu['items'] as $item): ?>
                        <li class="<?= ! empty($item['actif']) ? 'active' : '' ?>">
                            <a href="<?= base_url($item['url']) ?>">
                                <i class="fa <?= esc($item['icone']) ?>"></i>
                                <span><?= esc($item['libelle']) ?></span>
                                <?php if (! empty($item['compteur'])): ?>
                                    <span class="app-counter"><?= (int) $item['compteur'] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>

        <div class="app-sidebar-footer">
            <a href="mailto:support@talentflow.local"><i class="fa fa-life-ring"></i> Support RH</a>
            <?php if ($utilisateur): ?>
                <form method="post" action="<?= base_url('logout') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="app-logout-btn"><i class="fa fa-sign-out"></i> Déconnexion</button>
                </form>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="app-sidebar-footer app-sidebar-guest">
            <a href="<?= base_url('login') ?>"><i class="fa fa-sign-in"></i> Connexion</a>
            <a href="<?= base_url('inscription') ?>"><i class="fa fa-user-plus"></i> Inscription</a>
        </div>
    <?php endif; ?>
</aside>