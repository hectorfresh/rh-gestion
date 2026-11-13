<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-clock-o text-warning"></i> Mon pointage</h1>
        <p class="page-subtitle"><?= esc($salarie['matricule']) ?> — <?= date('d/m/Y') ?></p></div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="app-card">
            <h4 class="app-card-title">Aujourd’hui</h4>
            <?php if ($aujourdhui): ?>
                <p><strong>Entrée :</strong> <?= $aujourdhui['heure_entree'] ? date('H:i', strtotime($aujourdhui['heure_entree'])) : '—' ?></p>
                <p><strong>Sortie :</strong> <?= $aujourdhui['heure_sortie'] ? date('H:i', strtotime($aujourdhui['heure_sortie'])) : '—' ?></p>
                <p><strong>Statut :</strong> <?= tf_badge($aujourdhui['statut']) ?></p>
                <?php if ($aujourdhui['minutes_travaillees']): ?>
                    <p><strong>Temps :</strong> <?= round($aujourdhui['minutes_travaillees'] / 60, 2) ?> h</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">Aucun pointage aujourd’hui.</p>
            <?php endif; ?>

            <div class="app-actions">
                <?php if (! $aujourdhui || ! $aujourdhui['heure_entree']): ?>
                    <form method="post" action="<?= base_url('pointages/entree') ?>" style="display:inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-success"><i class="fa fa-sign-in"></i> Pointer l’entrée</button>
                    </form>
                <?php endif; ?>
                <?php if ($aujourdhui && $aujourdhui['heure_entree'] && ! $aujourdhui['heure_sortie']): ?>
                    <form method="post" action="<?= base_url('pointages/sortie') ?>" style="display:inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-warning"><i class="fa fa-sign-out"></i> Pointer la sortie</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="app-card">
            <h4 class="app-card-title">Planning de la semaine</h4>
            <?php if (empty($planning)): ?>
                <p class="text-muted">Aucun planning particulier : les horaires du contrat s’appliquent.</p>
            <?php else: ?>
                <table class="table table-condensed">
                    <thead><tr><th>Jour</th><th>Horaires</th><th>Type</th><th>Commentaire</th></tr></thead>
                    <tbody>
                    <?php foreach ($planning as $p): ?>
                        <tr>
                            <td><?= tf_date($p['jour'], 'l d/m') ?></td>
                            <td><?= $p['heure_debut'] ? substr($p['heure_debut'], 0, 5) . ' – ' . substr($p['heure_fin'], 0, 5) : '—' ?></td>
                            <td><?= tf_badge($p['type']) ?></td>
                            <td><?= esc($p['commentaire'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="app-card">
    <h4 class="app-card-title">Historique (30 derniers jours)</h4>
    <table class="table table-hover">
        <thead><tr><th>Jour</th><th>Entrée</th><th>Sortie</th><th>Temps</th><th>Heures sup.</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($pointages as $p): ?>
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
</div>
<?= $this->endSection() ?>
