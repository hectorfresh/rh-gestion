<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-file-text-o text-warning"></i> Contrats</h1>
        <p class="page-subtitle">Contrats générés automatiquement à la confirmation des propositions.</p></div>
</div>

<div class="app-card">
    <?php if (empty($contrats)): ?>
        <div class="empty-state"><i class="fa fa-file-o"></i>Aucun contrat.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Salarié</th><th>Poste</th><th>Type</th><th>Salaire</th><th>Début</th><th>Fin</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($contrats as $c): ?>
                    <tr>
                        <td><?= (int) $c['id'] ?></td>
                        <td><?= esc(trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? '')) ?: '—') ?><br><small class="text-muted"><?= esc($c['matricule'] ?? '') ?></small></td>
                        <td><?= esc($c['poste'] ?? '—') ?><br><small class="text-muted"><?= esc($c['departement'] ?? '') ?></small></td>
                        <td><?= esc($c['type_contrat']) ?></td>
                        <td class="money"><?= tf_money($c['salaire_mensuel']) ?></td>
                        <td><?= tf_date($c['date_debut']) ?></td>
                        <td><?= tf_date($c['date_fin']) ?></td>
                        <td><?= tf_badge($c['statut']) ?></td>
                        <td class="text-right"><a class="btn btn-xs btn-default" href="<?= base_url('contrats/' . $c['id']) ?>">Voir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
