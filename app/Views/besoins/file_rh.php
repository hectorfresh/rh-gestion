<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-inbox text-warning"></i> File de validation RH</h1>
        <p class="page-subtitle">Besoins en attente de la décision RH. La finance a déjà été calculée à la soumission.</p>
    </div>
    <a href="<?= base_url('besoins') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Tous les besoins</a>
</div>

<div class="app-card">
    <?php if (empty($besoins)): ?>
        <div class="empty-state"><i class="fa fa-check-circle"></i>Aucun besoin en attente de validation RH.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th><th>Poste</th><th>Département</th><th>Demandeur</th>
                        <th>Nb</th><th>Coût annuel</th><th>Finance</th><th>Budget disponible</th>
                        <th>Soumis le</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($besoins as $b): ?>
                    <?php $finance = $b['finance'] ?? null; ?>
                    <tr>
                        <td><strong>#<?= (int) $b['id'] ?></strong></td>
                        <td><?= esc($b['poste']) ?><br><small class="text-muted"><?= esc(ucfirst($b['type_besoin'])) ?></small></td>
                        <td><?= esc($b['departement']) ?></td>
                        <td><?= esc(($b['demandeur_prenom'] ?? '') . ' ' . ($b['demandeur_nom'] ?? '')) ?></td>
                        <td><?= (int) $b['nombre'] ?></td>
                        <td class="money"><?= tf_money($b['cout'] ?? 0) ?></td>
                        <td><?= tf_decision($b['decisions']['finance']['decision'] ?? 'en_attente') ?></td>
                        <td class="money <?= ($finance['reste_apres_cout'] ?? 0) < 0 ? 'text-danger' : '' ?>">
                            <?= tf_money($finance['reste_apres_cout'] ?? 0) ?>
                        </td>
                        <td><?= tf_datetime($b['date_creation']) ?></td>
                        <td class="text-right">
                            <a class="btn btn-sm btn-warning" href="<?= base_url('besoins/' . $b['id']) ?>">
                                <i class="fa fa-user-check"></i> Traiter
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
