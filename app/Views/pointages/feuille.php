<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-file-text-o text-warning"></i> Feuille de temps mensuelle</h1>
        <p class="page-subtitle"><?= esc($salarie['prenom'] . ' ' . $salarie['nom']) ?> — <?= esc($mois) ?></p></div>
    <a href="<?= base_url('pointages/mes') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<div class="app-card">
    <form method="get" class="form-inline" style="margin-bottom:15px">
        <label>Mois :</label>
        <input type="month" name="mois" class="form-control input-sm" value="<?= esc($mois) ?>">
        <button class="btn btn-sm btn-warning">Afficher</button>
    </form>

    <table class="table table-hover">
        <thead><tr><th>Jour</th><th>Entrée</th><th>Sortie</th><th>Temps</th><th>Heures sup.</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($feuille['pointages'] as $p): ?>
            <tr>
                <td><?= tf_date($p['jour'], 'D d/m/Y') ?></td>
                <td><?= $p['heure_entree'] ? date('H:i', strtotime($p['heure_entree'])) : '—' ?></td>
                <td><?= $p['heure_sortie'] ? date('H:i', strtotime($p['heure_sortie'])) : '—' ?></td>
                <td><?= $p['minutes_travaillees'] ? round($p['minutes_travaillees'] / 60, 2) . ' h' : '—' ?></td>
                <td><?= $p['minutes_sup'] ? round($p['minutes_sup'] / 60, 2) . ' h' : '—' ?></td>
                <td><?= tf_badge($p['statut']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p class="text-muted">
        Jours pointés : <?= (int) $feuille['totaux']['jours'] ?> ·
        Temps : <?= round($feuille['totaux']['minutes'] / 60, 2) ?> h ·
        Heures sup. : <?= round($feuille['totaux']['sup'] / 60, 2) ?> h ·
        Retards : <?= (int) $feuille['totaux']['retards'] ?>
    </p>
</div>
<?= $this->endSection() ?>