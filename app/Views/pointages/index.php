<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-clock-o text-warning"></i> Pointages du <?= tf_date($jour) ?></h1>
        <p class="page-subtitle">Présence, retards et heures supplémentaires.</p></div>
    <div>
        <a href="<?= base_url('pointages/anomalies') ?>" class="btn btn-default"><i class="fa fa-exclamation-triangle"></i> Anomalies</a>
        <a href="<?= base_url('absences') ?>" class="btn btn-default"><i class="fa fa-user-times"></i> Absences</a>
        <a href="<?= base_url('planning') ?>" class="btn btn-default"><i class="fa fa-calendar"></i> Planning</a>
    </div>
</div>

<div class="app-card">
    <div class="form-inline" style="margin-bottom:15px">
        <form method="get" class="form-inline" style="display:inline-block">
            <label>Jour :</label>
            <input type="date" name="jour" class="form-control input-sm" value="<?= esc($jour) ?>">
            <button class="btn btn-sm btn-warning">Afficher</button>
        </form>
        <form method="post" action="<?= base_url('pointages/absents') ?>" style="display:inline-block;margin-left:8px">
            <?= csrf_field() ?>
            <input type="hidden" name="jour" value="<?= esc($jour) ?>">
            <button class="btn btn-sm btn-default" onclick="return confirm('Marquer absents les salariés sans pointage ?')">
                <i class="fa fa-user-times"></i> Marquer les absents
            </button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Matricule</th><th>Salarié</th><th>Poste</th><th>Entrée</th><th>Sortie</th><th>Temps</th><th>Sup.</th><th>Statut</th><th>Anomalies</th></tr></thead>
            <tbody>
            <?php foreach ($pointages as $p): ?>
                <tr>
                    <td><?= esc($p['matricule']) ?></td>
                    <td><?= esc($p['prenom'] . ' ' . $p['nom']) ?></td>
                    <td><?= esc($p['poste']) ?><br><small class="text-muted"><?= esc($p['departement']) ?></small></td>
                    <td><?= $p['heure_entree'] ? date('H:i', strtotime($p['heure_entree'])) : '—' ?></td>
                    <td><?= $p['heure_sortie'] ? date('H:i', strtotime($p['heure_sortie'])) : '—' ?></td>
                    <td><?= $p['minutes_travaillees'] ? round($p['minutes_travaillees'] / 60, 2) . ' h' : '—' ?></td>
                    <td><?= $p['minutes_sup'] ? round($p['minutes_sup'] / 60, 2) . ' h' : '—' ?></td>
                    <td><?= $p['statut'] ? tf_badge($p['statut']) : '<span class="status-badge status-gray">Non pointé</span>' ?></td>
                    <td><?php if ((int) ($p['nb_anomalies'] ?? 0) > 0): ?><span class="status-badge status-red"><?= (int) $p['nb_anomalies'] ?></span><?php else: ?>—<?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
