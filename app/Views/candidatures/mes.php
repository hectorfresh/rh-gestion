<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-file-text-o text-warning"></i> Mes candidatures</h1>
        <p class="page-subtitle">Suivez l’avancement de vos candidatures.</p></div>
    <a href="<?= base_url('annonces') ?>" class="btn btn-warning"><i class="fa fa-search"></i> Voir les annonces</a>
</div>

<div class="app-card">
    <?php if (empty($candidatures)): ?>
        <div class="empty-state"><i class="fa fa-inbox"></i>Vous n’avez pas encore postulé.<br><br>
            <a class="btn btn-warning" href="<?= base_url('annonces') ?>">Découvrir les annonces</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Annonce</th><th>Poste</th><th>Département</th><th>Date</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($candidatures as $c): ?>
                    <tr>
                        <td><?= (int) $c['id'] ?></td>
                        <td><?= esc($c['annonce']) ?></td>
                        <td><?= esc($c['poste']) ?></td>
                        <td><?= esc($c['departement']) ?></td>
                        <td><?= tf_date($c['date_candidature']) ?></td>
                        <td><?= tf_badge($c['statut']) ?></td>
                        <td class="text-right"><a class="btn btn-xs btn-default" href="<?= base_url('candidatures/' . $c['id']) ?>">Détail</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
