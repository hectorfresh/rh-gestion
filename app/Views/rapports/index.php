<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-bar-chart text-warning"></i> Rapports</h1>
        <p class="page-subtitle">Indicateurs RH et exports CSV.</p></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="app-card">
            <h4 class="app-card-title">Effectif par département</h4>
            <table class="table table-condensed">
                <thead><tr><th>Département</th><th class="text-right">Effectif</th></tr></thead>
                <tbody>
                <?php foreach ($salaries_par_departement as $s): ?>
                    <tr><td><?= esc($s['libelle']) ?></td><td class="text-right"><?= (int) $s['total'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="app-card">
            <h4 class="app-card-title">Contrats actifs par type</h4>
            <table class="table table-condensed">
                <thead><tr><th>Type</th><th class="text-right">Nombre</th></tr></thead>
                <tbody>
                <?php foreach ($contrats_par_type as $c): ?>
                    <tr><td><?= esc($c['type_contrat']) ?></td><td class="text-right"><?= (int) $c['total'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="app-card">
            <h4 class="app-card-title">Entonnoir de recrutement</h4>
            <table class="table table-condensed">
                <thead><tr><th>Statut candidature</th><th class="text-right">Nombre</th></tr></thead>
                <tbody>
                <?php foreach ($candidatures_par_statut as $c): ?>
                    <tr><td><?= tf_badge($c['libelle']) ?></td><td class="text-right"><?= (int) $c['total'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="app-card">
            <h4 class="app-card-title">Congés acceptés par type</h4>
            <table class="table table-condensed">
                <thead><tr><th>Type</th><th class="text-right">Demandes</th><th class="text-right">Jours</th></tr></thead>
                <tbody>
                <?php foreach ($conges_par_type as $c): ?>
                    <tr><td><?= esc($c['libelle']) ?></td><td class="text-right"><?= (int) $c['total'] ?></td><td class="text-right"><?= number_format((float) $c['jours'], 1, ',', ' ') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (! empty($budgets)): ?>
    <div class="app-card">
        <h4 class="app-card-title">Budgets <?= (int) $annee ?></h4>
        <table class="table table-hover">
            <thead><tr><th>Département</th><th class="text-right">Effectifs</th><th class="text-right">Budget</th><th class="text-right">Masse salariale</th><th class="text-right">Engagements</th><th class="text-right">Disponible</th></tr></thead>
            <tbody>
            <?php foreach ($budgets as $b): ?>
                <tr class="<?= $b['resume']['disponible'] < 0 ? 'danger' : '' ?>">
                    <td><?= esc($b['libelle']) ?></td>
                    <td class="text-right"><?= (int) $b['effectifs'] ?></td>
                    <td class="text-right money"><?= tf_money($b['resume']['budget']) ?></td>
                    <td class="text-right money"><?= tf_money($b['resume']['masse']) ?></td>
                    <td class="text-right money"><?= tf_money($b['resume']['engagements']) ?></td>
                    <td class="text-right money <?= $b['resume']['disponible'] < 0 ? 'text-danger' : 'text-success' ?>"><?= tf_money($b['resume']['disponible']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="text-muted small">Export CSV : <a href="<?= base_url('rapports/export/budget?annee=' . $annee) ?>">budget</a>.</p>
    </div>
<?php endif; ?>

<div class="row">
    <?php if (can_show('paie')): ?>
        <div class="col-md-6">
            <div class="app-card">
                <h4 class="app-card-title">Paie du mois</h4>
                <p><strong>Salariés :</strong> <?= (int) $paie['salaries'] ?></p>
                <p><strong>Brut :</strong> <span class="money"><?= tf_money($paie['brut']) ?></span></p>
                <p><strong>Net :</strong> <span class="money"><?= tf_money($paie['net']) ?></span></p>
                <a href="<?= base_url('paie') ?>" class="btn btn-default btn-sm">Voir la paie variable</a>
            </div>
        </div>
    <?php endif; ?>
    <?php if (can_show('rapports', 'exporter')): ?>
        <div class="col-md-6">
            <div class="app-card">
                <h4 class="app-card-title">Exports CSV</h4>
                <div class="app-actions">
                    <a class="btn btn-warning" href="<?= base_url('rapports/export/salaries') ?>"><i class="fa fa-download"></i> Salariés</a>
                    <a class="btn btn-warning" href="<?= base_url('rapports/export/candidatures') ?>"><i class="fa fa-download"></i> Candidatures</a>
                    <a class="btn btn-warning" href="<?= base_url('rapports/export/conges') ?>"><i class="fa fa-download"></i> Congés</a>
                    <a class="btn btn-warning" href="<?= base_url('rapports/export/pointages') ?>"><i class="fa fa-download"></i> Pointages</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
