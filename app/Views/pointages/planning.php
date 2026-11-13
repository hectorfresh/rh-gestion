<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-calendar text-warning"></i> Planning de travail</h1>
        <p class="page-subtitle">Horaires prévus, repos, congés et jours fériés.</p></div>
    <a href="<?= base_url('pointages') ?>" class="btn btn-default"><i class="fa fa-clock-o"></i> Pointages</a>
</div>

<div class="row">
    <?php if ($staff): ?>
        <div class="col-md-4">
            <div class="app-card">
                <h4 class="app-card-title">Ajouter / modifier</h4>
                <form method="post" action="<?= base_url('planning') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Salarié</label>
                        <select name="salarie_id" class="form-control" required>
                            <?php foreach ($salaries as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"><?= esc($s['prenom'] . ' ' . $s['nom']) ?> (<?= esc($s['matricule']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Jour</label>
                        <input type="date" name="jour" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group"><label>Type</label>
                        <select name="type" class="form-control">
                            <option value="travail">Travail</option>
                            <option value="repos">Repos</option>
                            <option value="conge">Congé</option>
                            <option value="ferie">Férié</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-sm-6"><div class="form-group"><label>Début</label>
                            <input type="time" name="heure_debut" class="form-control" value="08:00"></div></div>
                        <div class="col-sm-6"><div class="form-group"><label>Fin</label>
                            <input type="time" name="heure_fin" class="form-control" value="17:00"></div></div>
                    </div>
                    <div class="form-group"><label>Commentaire</label>
                        <input type="text" name="commentaire" class="form-control"></div>
                    <button class="btn btn-warning btn-block">Enregistrer</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="<?= $staff ? 'col-md-8' : 'col-md-12' ?>">
        <div class="app-card">
            <h4 class="app-card-title">Planning</h4>
            <?php if (empty($planning)): ?>
                <div class="empty-state"><i class="fa fa-calendar-o"></i>Aucun planning enregistré.</div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead><tr><th>Jour</th><th>Salarié</th><th>Horaires</th><th>Type</th><th>Commentaire</th></tr></thead>
                    <tbody>
                    <?php foreach ($planning as $p): ?>
                        <tr>
                            <td><?= tf_date($p['jour'], 'D d/m/Y') ?></td>
                            <td><?= esc($p['prenom'] . ' ' . $p['nom']) ?><br><small class="text-muted"><?= esc($p['matricule']) ?></small></td>
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
<?= $this->endSection() ?>
