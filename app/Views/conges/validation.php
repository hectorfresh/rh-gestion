<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-check-square text-warning"></i> Validation des congés</h1>
        <p class="page-subtitle">
            <?= $role === 'manager' ? 'Demandes de votre équipe en attente de validation manager.' : 'Demandes validées par le manager, à traiter par le RH.' ?>
        </p></div>
    <a href="<?= base_url('conges/soldes') ?>" class="btn btn-default"><i class="fa fa-list"></i> Soldes</a>
</div>

<div class="app-card">
    <?php if (empty($demandes)): ?>
        <div class="empty-state"><i class="fa fa-check-circle"></i>Aucune demande à traiter.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Salarié</th><th>Type</th><th>Période</th><th>Jours</th><th>Statut</th><th>Demandée le</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($demandes as $d): ?>
                    <tr>
                        <td><?= (int) $d['id'] ?></td>
                        <td><?= esc($d['prenom'] . ' ' . $d['nom']) ?><br><small class="text-muted"><?= esc($d['matricule']) ?></small></td>
                        <td><?= esc($d['type']) ?></td>
                        <td><?= tf_date($d['date_debut']) ?> → <?= tf_date($d['date_fin']) ?></td>
                        <td><?= number_format((float) $d['nombre_jours'], 1, ',', ' ') ?></td>
                        <td><?= tf_badge($d['statut']) ?></td>
                        <td><?= tf_date($d['date_demande']) ?></td>
                        <td class="text-right"><a class="btn btn-xs btn-warning" href="<?= base_url('conges/' . $d['id']) ?>">Traiter</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
