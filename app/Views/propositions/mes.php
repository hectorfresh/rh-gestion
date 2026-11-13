<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-handshake-o text-warning"></i> Mes propositions</h1>
        <p class="page-subtitle">Répondez aux éléments proposés par l’entreprise.</p></div>
</div>

<div class="app-card">
    <?php if (empty($propositions)): ?>
        <div class="empty-state"><i class="fa fa-handshake-o"></i>Aucune proposition pour le moment.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Poste</th><th>Annonce</th><th>Statut</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($propositions as $p): ?>
                    <tr>
                        <td><?= (int) $p['id'] ?></td>
                        <td><?= esc($p['poste']) ?><br><small class="text-muted"><?= esc($p['departement']) ?></small></td>
                        <td><?= esc($p['annonce']) ?></td>
                        <td><?= tf_statut_proposition($p['statut']) ?></td>
                        <td><?= tf_date($p['date_creation']) ?></td>
                        <td class="text-right"><a class="btn btn-xs btn-warning" href="<?= base_url('propositions/' . $p['id']) ?>">Ouvrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
