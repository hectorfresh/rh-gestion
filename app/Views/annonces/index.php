<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="hero">
    <h1><i class="fa fa-bullhorn"></i> Rejoignez notre équipe</h1>
    <p>Découvrez les postes ouverts et déposez votre candidature en quelques minutes.</p>
    <?php if (! $utilisateur): ?>
        <a href="<?= base_url('inscription') ?>" class="btn btn-default btn-lg"><i class="fa fa-user-plus"></i> Créer un compte candidat</a>
    <?php endif; ?>
</div>

<div class="app-page-header">
    <div>
        <h1>Annonces ouvertes</h1>
        <p class="page-subtitle"><?= count($annonces) ?> opportunité(s) disponible(s).</p>
    </div>
    <?php if (in_array($role, ['rh', 'dg'], true)): ?>
        <a href="<?= base_url('admin/annonces') ?>" class="btn btn-default"><i class="fa fa-cogs"></i> Gérer les annonces</a>
    <?php endif; ?>
</div>

<?php if (empty($annonces)): ?>
    <div class="app-card empty-state"><i class="fa fa-search"></i>Aucune annonce ouverte pour le moment.</div>
<?php else: ?>
    <div class="row">
        <?php foreach ($annonces as $a): ?>
            <div class="col-md-6">
                <div class="app-card">
                    <div class="app-page-header" style="margin-bottom:10px">
                        <h3 style="margin:0;font-size:18px"><?= esc($a['titre']) ?></h3>
                        <span class="status-badge status-blue"><?= (int) $a['nombre'] ?> poste(s)</span>
                    </div>
                    <p class="text-muted">
                        <i class="fa fa-briefcase"></i> <?= esc($a['poste']) ?> ·
                        <i class="fa fa-building-o"></i> <?= esc($a['departement']) ?>
                    </p>
                    <p><?= esc(mb_strimwidth(strip_tags((string) $a['description']), 0, 220, '…')) ?></p>
                    <p class="text-muted small">
                        <i class="fa fa-money"></i> <?= tf_money($a['salaire_estime'] ?: $a['salaire_base']) ?> / mois ·
                        <i class="fa fa-calendar"></i> publiée le <?= tf_date($a['date_publication']) ?>
                        <?php if ($a['date_limite']): ?> · limite <?= tf_date($a['date_limite']) ?><?php endif; ?>
                    </p>
                    <a href="<?= base_url('annonces/' . $a['id']) ?>" class="btn btn-warning">Voir l’annonce</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
