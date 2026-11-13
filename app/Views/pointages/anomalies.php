<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-exclamation-triangle text-warning"></i> Anomalies de pointage</h1>
        <p class="page-subtitle">Retards, départs anticipés, pointages incomplets et heures supplémentaires.</p></div>
    <a href="<?= base_url('pointages') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<div class="app-card">
    <?php if (empty($anomalies)): ?>
        <div class="empty-state"><i class="fa fa-check-circle"></i>Aucune anomalie.</div>
    <?php else: ?>
        <table class="table table-hover">
            <thead><tr><th>#</th><th>Jour</th><th>Salarié</th><th>Type</th><th>Description</th><th>État</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($anomalies as $a): ?>
                <tr>
                    <td><?= (int) $a['id'] ?></td>
                    <td><?= tf_date($a['jour']) ?></td>
                    <td><?= esc($a['prenom'] . ' ' . $a['nom']) ?><br><small class="text-muted"><?= esc($a['matricule']) ?></small></td>
                    <td><?= tf_badge($a['type']) ?></td>
                    <td><?= esc($a['description']) ?></td>
                    <td><?= (int) $a['resolue'] === 1 ? '<span class="status-badge status-green">Résolue</span>' : '<span class="status-badge status-orange">À traiter</span>' ?></td>
                    <td class="text-right">
                        <?php if ((int) $a['resolue'] === 0 && can_show('pointages', 'modifier')): ?>
                            <form method="post" action="<?= base_url('pointages/anomalies/' . $a['id'] . '/resoudre') ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-success">Résoudre</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
