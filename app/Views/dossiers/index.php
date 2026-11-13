<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-folder-open text-warning"></i> Dossiers administratifs</h1>
        <p class="page-subtitle">Gestion électronique des documents (GED).</p></div>
</div>

<div class="app-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Matricule</th><th>Salarié</th><th>Poste</th><th>Département</th><th>Documents</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($salaries as $s): ?>
                <tr>
                    <td><?= esc($s['matricule']) ?></td>
                    <td><?= esc($s['prenom'] . ' ' . $s['nom']) ?></td>
                    <td><?= esc($s['poste']) ?></td>
                    <td><?= esc($s['departement']) ?></td>
                    <td><span class="status-badge <?= (int) $s['nb_documents'] > 0 ? 'status-green' : 'status-gray' ?>"><?= (int) $s['nb_documents'] ?></span></td>
                    <td class="text-right"><a class="btn btn-xs btn-warning" href="<?= base_url('dossiers/' . $s['id']) ?>">Ouvrir le dossier</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
