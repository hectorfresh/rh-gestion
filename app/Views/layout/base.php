<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($titre ?? 'Gestion') ?> — Gestion</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<?php $utilisateur = $utilisateur ?? null; $role = $role ?? ''; ?>
<?php $pageAuth = in_array(trim(uri_string(), '/'), ['login', 'inscription'], true); ?>
<body class="app-shell <?= empty($utilisateur) ? 'app-guest' : '' ?> <?= $pageAuth ? 'app-auth-page' : '' ?>">

    <?php if (! $pageAuth): ?>
        <?= $this->include('layout/navbar') ?>
    <?php endif; ?>

    <div class="app-main <?= $pageAuth ? 'app-main-full' : '' ?>">
        <?php if (! $pageAuth): ?>
        <div class="app-topbar">
            <button type="button" class="app-menu-toggle" id="appMenuToggle" aria-label="Menu">
                <i class="fa fa-bars"></i>
            </button>

            <div class="app-topbar-title">
                <span class="app-module-name"><?= esc($titre ?? 'TalentFlow') ?></span>
                <span class="app-version-badge">v2.0</span>
            </div>

            <form class="app-global-search" method="get" action="<?= base_url($utilisateur && $role !== 'candidat' ? 'salaries' : 'annonces') ?>">
                <i class="fa fa-search"></i>
                <input type="text" name="q" placeholder="Recherche globale…" value="<?= esc($recherche ?? '') ?>">
            </form>

            <div class="app-topbar-actions">
                <?php if ($utilisateur): ?>
                    <?php
                    $actionPrincipale = $action_principale ?? null;
                    if (! $actionPrincipale) {
                        $actionsParPage = [
                            'salaries' => ['libelle' => 'Nouveau salarié', 'url' => 'salaries/' . (int) ($salarie['id'] ?? 0) . '/modifier', 'role' => ['rh', 'dg']],
                            'contrats' => ['libelle' => 'Nouveau contrat', 'url' => 'contrats/echeance', 'role' => ['rh', 'dg']],
                            'besoins' => ['libelle' => 'Nouveau besoin', 'url' => 'besoins/creer', 'role' => ['manager', 'dg']],
                            'annonces' => ['libelle' => 'Nouvelle annonce', 'url' => 'admin/annonces/creer', 'role' => ['rh', 'dg']],
                            'conges' => ['libelle' => 'Nouvelle demande', 'url' => 'conges/creer', 'role' => ['rh', 'dg', 'manager', 'employe']],
                            'pointage' => ['libelle' => 'Pointer', 'url' => 'pointages/mes', 'role' => ['rh', 'dg', 'manager', 'employe']],
                        ];
                        $actionPrincipale = $actionsParPage[$active ?? ''] ?? null;
                        if ($actionPrincipale && ! in_array($role, $actionPrincipale['role'], true)) {
                            $actionPrincipale = null;
                        }
                    }
                    ?>
                    <?php if ($actionPrincipale): ?>
                        <a href="<?= base_url($actionPrincipale['url']) ?>" class="btn btn-primary btn-sm app-primary-action">
                            <i class="fa fa-plus"></i> <?= esc($actionPrincipale['libelle']) ?>
                        </a>
                    <?php endif; ?>

                    <?php if (can_show('audit')): ?>
                        <a href="<?= base_url('audit') ?>" class="btn btn-default btn-sm" title="Historique / audit">
                            <i class="fa fa-history"></i> Historique
                        </a>
                    <?php endif; ?>

                    <div class="app-bell">
                        <a href="<?= base_url('notifications') ?>" class="app-bell-link">
                            <i class="fa fa-bell"></i>
                            <?php if (($notifications_non_lues ?? 0) > 0): ?>
                                <span class="app-bell-dot"></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <div class="app-user-chip">
                        <span class="app-avatar"><?= esc(strtoupper(substr($utilisateur['prenom'] ?? 'U', 0, 1) . substr($utilisateur['nom'] ?? '', 0, 1))) ?></span>
                        <span class="app-user-meta">
                            <strong><?= esc(trim(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? ''))) ?></strong>
                            <small><?= esc($role) ?></small>
                        </span>
                    </div>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="btn btn-primary btn-sm">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="app-content">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fa fa-check-circle"></i> <?= esc(session()->getFlashdata('success')) ?>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fa fa-exclamation-circle"></i> <?= esc(session()->getFlashdata('error')) ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($page_header)): ?>
                <?= $this->include('partials/page_header', ['page_header' => $page_header]) ?>
            <?php endif; ?>

            <?= $this->renderSection('content') ?>
        </div>
    </div>

    <script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.min.js') ?>"></script>
    <script>
        (function () {
            var toggle = document.getElementById('appMenuToggle');
            if (toggle) {
                toggle.addEventListener('click', function () {
                    document.body.classList.toggle('sidebar-open');
                });
            }
        })();
    </script>
</body>
</html>