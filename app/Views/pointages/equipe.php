<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-users text-warning"></i> Récapitulatif d’équipe</h1>
        <p class="page-subtitle">Temps travaillé et heures supplémentaires par salarié.</p></div>
    <a href="<?= base_url('pointages') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Pointages</a>
</div>

<div class="app-card">
    <form method="get" class="form-inline" style="margin-bottom:15px">
        <label>Mois :</label>
        <input type="month" name="mois" class="form-control input-sm" value="<?= esc($mois) ?>">
        <button class="btn btn-sm btn-warning">Afficher</button>
    </form>

    <?php if ($this->role === 'rh' || $this->role === 'dg'): ?>
        <form method="post" action="<?= base_url('pointages/calcul') ?>" class="form-inline" style="margin-bottom:15px">
            <?= csrf_field() ?>
            <input type="hidden" name="mois" value="<?= esc($mois) ?>">
            <select name="salarie_id" class="form-control input-sm">
                <option value="0">Tous les salariés</option>
            </select>
            <button class="btn btn-sm btn-warning"><i class="fa fa-calculator"></i> Calculer le mois</button>
        </form>
    <?php endif; ?>

    <table class="table table-hover">
        <thead><tr><th>Salarié</th><th>Jours pointés</th><th>Temps</th><th>Heures sup.</th><th>Retards</th></tr></thead>
        <tbody>
        <?php foreach ($recap as $ligne): ?>
            <tr>
                <td><?= esc($ligne['salarie']['prenom'] . ' ' . $ligne['salarie']['nom']) ?><br><small class="text-muted"><?= esc($ligne['salarie']['matricule']) ?></small></td>
                <td><?= (int) $ligne['totaux']['jours'] ?></td>
                <td><?= round($ligne['totaux']['minutes'] / 60, 2) ?> h</td>
                <td><?= round($ligne['totaux']['sup'] / 60, 2) ?> h</td>
                <td><?= (int) $ligne['totaux']['retards'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recap)): ?>
            <tr><td colspan="5" class="text-center text-muted">Aucune donnée.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>