<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-clipboard text-warning"></i> <?= $role === 'manager' ? 'Mes besoins' : 'Besoins de recrutement' ?></h1>
        <p class="page-subtitle">
            <?= $role === 'manager'
                ? 'Vos demandes et leur avancement dans le circuit RH → finance → DG.'
                : 'Tous les besoins et leur circuit de validation.' ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if (in_array($role, ['rh', 'dg'], true)): ?>
            <a href="<?= base_url('besoins/file-rh') ?>" class="btn btn-default">
                <i class="fa fa-inbox"></i> File RH
                <?php if (($nb_file_rh ?? 0) > 0): ?><span class="badge"><?= (int) $nb_file_rh ?></span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if ($role === 'dg'): ?>
            <a href="<?= base_url('besoins/file-dg') ?>" class="btn btn-danger">
                <i class="fa fa-gavel"></i> File DG
                <?php if (($nb_file_dg ?? 0) > 0): ?><span class="badge"><?= (int) $nb_file_dg ?></span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if (in_array($role, ['manager', 'dg'], true)): ?>
            <a href="<?= base_url('besoins/creer') ?>" class="btn btn-warning"><i class="fa fa-plus"></i> Nouveau besoin</a>
        <?php endif; ?>
    </div>
</div>

<div class="app-card">
    <?php if ($statut_filtre): ?>
        <p class="text-muted">Filtre : <?= esc($statut_filtre) ?> — <a href="<?= base_url('besoins') ?>">tout afficher</a></p>
    <?php endif; ?>

    <?php if (empty($besoins)): ?>
        <div class="empty-state"><i class="fa fa-inbox"></i>Aucun besoin à afficher.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th><th>Poste</th><th>Département</th>
                        <?php if ($role !== 'manager'): ?><th>Demandeur</th><?php endif; ?>
                        <th>Nb</th><th>Coût annuel</th><th>Statut</th><th>Étapes</th><th>Date</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($besoins as $b): ?>
                    <?php $decisions = $b['decisions'] ?? []; ?>
                    <tr>
                        <td><strong>#<?= (int) $b['id'] ?></strong></td>
                        <td>
                            <?= esc($b['poste']) ?>
                            <br><small class="text-muted"><?= esc(ucfirst($b['type_besoin'])) ?></small>
                        </td>
                        <td><?= esc($b['departement']) ?></td>
                        <?php if ($role !== 'manager'): ?>
                            <td><?= esc(($b['demandeur_prenom'] ?? '') . ' ' . ($b['demandeur_nom'] ?? '')) ?></td>
                        <?php endif; ?>
                        <td><?= (int) $b['nombre'] ?></td>
                        <td class="money"><?= tf_money($b['cout'] ?? 0) ?></td>
                        <td><?= tf_badge($b['statut']) ?></td>
                        <td>
                            <span class="workflow-mini" title="RH">
                                RH <?= tf_decision($decisions['rh']['decision'] ?? 'en_attente') ?>
                            </span>
                            <span class="workflow-mini" title="Finance">
                                Fin. <?= tf_decision($decisions['finance']['decision'] ?? 'en_attente') ?>
                            </span>
                            <span class="workflow-mini" title="DG">
                                DG <?= tf_decision($decisions['dg']['decision'] ?? 'en_attente') ?>
                            </span>
                        </td>
                        <td><?= tf_date($b['date_creation']) ?></td>
                        <td class="text-right">
                            <a class="btn btn-xs btn-default" href="<?= base_url('besoins/' . $b['id']) ?>">Détail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
