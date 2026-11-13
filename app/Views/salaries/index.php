<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-id-card text-warning"></i> Salariés</h1>
        <p class="page-subtitle">Fiches et dossiers administratifs du personnel.</p></div>
</div>

<div class="app-card">
    <form method="get" class="form-inline" style="margin-bottom:15px">
        <input type="text" name="q" class="form-control input-sm" placeholder="Recherche nom, matricule, email" value="<?= esc($recherche) ?>">
        <select name="departement_id" class="form-control input-sm">
            <option value="">— Département —</option>
            <?php foreach ($departements as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= (int) $departement_id === (int) $d['id'] ? 'selected' : '' ?>><?= esc($d['libelle']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="actif" class="form-control input-sm">
            <option value="">— État —</option>
            <option value="1" <?= $actif === '1' ? 'selected' : '' ?>>Actif</option>
            <option value="0" <?= $actif === '0' ? 'selected' : '' ?>>Inactif</option>
        </select>
        <button class="btn btn-sm btn-warning">Filtrer</button>
        <a href="<?= base_url('salaries') ?>" class="btn btn-sm btn-default">Réinitialiser</a>
    </form>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Matricule</th><th>Nom</th><th>Poste</th><th>Département</th><th>Régime</th><th>Embauche</th><th>Documents</th><th>État</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($salaries as $s): ?>
                <tr>
                    <td><strong><?= esc($s['matricule']) ?></strong></td>
                    <td><?= esc($s['prenom'] . ' ' . $s['nom']) ?><br><small class="text-muted"><?= esc($s['email']) ?></small></td>
                    <td><?= esc($s['poste']) ?></td>
                    <td><?= esc($s['departement']) ?></td>
                    <td><?= esc($s['regime']) ?></td>
                    <td><?= tf_date($s['date_embauche']) ?></td>
                    <td><?= (int) $s['nb_documents'] ?></td>
                    <td><?= tf_badge((int) $s['actif'] === 1 ? 'Actif' : 'Inactif') ?></td>
                    <td class="text-right">
                        <a class="btn btn-xs btn-default" href="<?= base_url('salaries/' . $s['id']) ?>">Fiche</a>
                        <?php if (can_show('dossiers')): ?>
                            <a class="btn btn-xs btn-warning" href="<?= base_url('dossiers/' . $s['id']) ?>">Dossier</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($salaries)): ?>
                <tr><td colspan="9" class="text-center text-muted">Aucun salarié.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="<?= $i === $page ? 'active' : '' ?>">
                    <a href="<?= base_url('salaries?q=' . urlencode($recherche) . '&departement_id=' . $departement_id . '&actif=' . urlencode((string) $actif) . '&page=' . $i) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
