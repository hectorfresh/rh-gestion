<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-calendar text-warning"></i> Calendrier d’équipe</h1>
        <p class="page-subtitle">Congés validés et en attente.</p></div>
    <a href="<?= base_url('conges/validation') ?>" class="btn btn-default"><i class="fa fa-check-square"></i> Validation</a>
</div>

<div class="app-card">
    <form method="get" class="form-inline" style="margin-bottom:15px">
        <label>Mois :</label>
        <input type="month" name="mois" class="form-control input-sm" value="<?= esc($mois) ?>">
        <button class="btn btn-sm btn-warning">Afficher</button>
    </form>

    <table class="table table-hover">
        <thead><tr><th>Salarié</th><th>Type</th><th>Du</th><th>Au</th><th>Jours</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($demandes as $d): ?>
            <tr>
                <td><?= esc($d['prenom'] . ' ' . $d['nom']) ?></td>
                <td><?= esc($d['type']) ?></td>
                <td><?= tf_date($d['date_debut']) ?></td>
                <td><?= tf_date($d['date_fin']) ?></td>
                <td><?= number_format((float) $d['nombre_jours'], 1, ',', ' ') ?></td>
                <td><?= tf_badge($d['statut']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($demandes)): ?>
            <tr><td colspan="6" class="text-center text-muted">Aucun congé sur cette période.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>