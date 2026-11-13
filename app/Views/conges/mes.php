<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-plane text-warning"></i> Mes congés</h1>
        <p class="page-subtitle">2,5 jours crédités par mois écoulé depuis l’embauche, 30 jours max par an.</p></div>
    <a href="<?= base_url('conges/creer') ?>" class="btn btn-warning"><i class="fa fa-plus"></i> Nouvelle demande</a>
</div>

<div class="row">
    <?php foreach ($soldes as $solde): ?>
        <div class="col-md-3 col-sm-6">
            <div class="app-stat <?= $solde['libelle'] === 'Congé payé' ? 'green' : '' ?>">
                <div class="stat-label"><?= esc($solde['libelle']) ?></div>
                <div class="stat-value"><?= number_format((float) $solde['disponible'], 1, ',', ' ') ?> j</div>
                <small class="text-muted"><?= number_format((float) $solde['jours_acquis'], 1, ',', ' ') ?> acquis · <?= number_format((float) $solde['jours_pris'], 1, ',', ' ') ?> pris</small>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="app-card">
    <h4 class="app-card-title">Mes demandes</h4>
    <?php if (empty($demandes)): ?>
        <div class="empty-state"><i class="fa fa-plane"></i>Aucune demande de congé.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Type</th><th>Du</th><th>Au</th><th>Jours</th><th>Demandée le</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($demandes as $d): ?>
                    <tr>
                        <td><?= (int) $d['id'] ?></td>
                        <td><?= esc($d['type']) ?></td>
                        <td><?= tf_date($d['date_debut']) ?></td>
                        <td><?= tf_date($d['date_fin']) ?></td>
                        <td><?= number_format((float) $d['nombre_jours'], 1, ',', ' ') ?></td>
                        <td><?= tf_date($d['date_demande']) ?></td>
                        <td><?= tf_badge($d['statut']) ?></td>
                        <td class="text-right"><a class="btn btn-xs btn-default" href="<?= base_url('conges/' . $d['id']) ?>">Détail</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
