<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-calendar text-warning"></i> Contrats à échéance sous 30 jours</h1>
        <p class="page-subtitle">Renouvellements à prévoir.</p></div>
    <a href="<?= base_url('contrats') ?>" class="btn btn-default"><i class="fa fa-list"></i> Tous les contrats</a>
</div>

<div class="app-card">
    <?php if (empty($contrats)): ?>
        <div class="empty-state"><i class="fa fa-calendar"></i>Aucun contrat à échéance sous 30 jours.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Salarié</th><th>Poste</th><th>Type</th><th>Salaire</th><th>Fin</th><th>Jours restants</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($contrats as $c): ?>
                    <?php $jours = (int) floor((strtotime($c['date_fin']) - strtotime(date('Y-m-d'))) / 86400); ?>
                    <tr>
                        <td><?= esc($c['prenom'] . ' ' . $c['nom']) ?><br><small class="text-muted"><?= esc($c['matricule']) ?></small></td>
                        <td><?= esc($c['poste']) ?><br><small class="text-muted"><?= esc($c['departement']) ?></small></td>
                        <td><?= esc($c['type_contrat']) ?></td>
                        <td class="money"><?= tf_money($c['salaire_mensuel']) ?></td>
                        <td><?= tf_date($c['date_fin']) ?></td>
                        <td><?= $jours ?> jour(s)</td>
                        <td class="text-right">
                            <a class="btn btn-xs btn-warning" href="<?= base_url('contrats/' . $c['id'] . '/renouveler') ?>">Renouveler</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>