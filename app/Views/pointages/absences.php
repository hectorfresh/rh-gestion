<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-user-times text-warning"></i> Absences</h1>
        <p class="page-subtitle">Absences maladie, injustifiées ou autorisées.</p></div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="app-card">
            <h4 class="app-card-title">Enregistrer une absence</h4>
            <form method="post" action="<?= base_url('absences') ?>">
                <?= csrf_field() ?>
                <div class="form-group"><label>Salarié</label>
                    <select name="salarie_id" class="form-control" required>
                        <?php foreach ($salaries as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= esc($s['prenom'] . ' ' . $s['nom']) ?> (<?= esc($s['matricule']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-sm-6"><div class="form-group"><label>Début</label>
                        <input type="date" name="date_debut" class="form-control" value="<?= date('Y-m-d') ?>" required></div></div>
                    <div class="col-sm-6"><div class="form-group"><label>Fin</label>
                        <input type="date" name="date_fin" class="form-control" value="<?= date('Y-m-d') ?>"></div></div>
                </div>
                <div class="form-group"><label>Type</label>
                    <select name="type" class="form-control">
                        <option value="maladie">Maladie</option>
                        <option value="injustifiee">Injustifiée</option>
                        <option value="autorisee">Autorisée</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="form-group"><label>Motif</label><input type="text" name="motif" class="form-control"></div>
                <div class="form-group"><label>Justificatif (référence)</label><input type="text" name="justificatif" class="form-control"></div>
                    <div class="form-group"><label><input type="checkbox" name="justifie" value="1"> Absence justifiée</label></div>
                <button class="btn btn-warning btn-block">Enregistrer</button>
            </form>
        </div>
    </div>
    <div class="col-md-7">
        <div class="app-card">
            <h4 class="app-card-title">Absences enregistrées</h4>
            <?php if (empty($absences)): ?>
                <div class="empty-state"><i class="fa fa-check-circle"></i>Aucune absence.</div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead><tr><th>Salarié</th><th>Du</th><th>Au</th><th>Type</th><th>Statut</th><th>Motif</th></tr></thead>
                    <tbody>
                    <?php foreach ($absences as $a): ?>
                        <tr>
                            <td><?= esc($a['prenom'] . ' ' . $a['nom']) ?><br><small class="text-muted"><?= esc($a['matricule']) ?></small></td>
                            <td><?= tf_date($a['date_debut']) ?></td>
                            <td><?= tf_date($a['date_fin']) ?></td>
                            <td><?= tf_badge($a['type']) ?></td>
                            <td><?= ! empty($a['justifie']) ? '<span class="status-badge status-green">Justifiée</span>' : '<span class="status-badge status-red">Non justifiée</span>' ?></td>
                            <td><?= esc($a['motif'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
