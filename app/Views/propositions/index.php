<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-handshake-o text-warning"></i> Propositions d’embauche</h1>
        <p class="page-subtitle">Questionnaires, contre-propositions et confirmations.</p></div>
    <div>
        <?php if (in_array($role, ['rh', 'dg'], true)): ?>
            <a class="btn btn-default" href="<?= base_url('propositions/file-rh') ?>"><i class="fa fa-inbox"></i> File RH</a>
        <?php endif; ?>
        <?php if ($role === 'dg'): ?>
            <a class="btn btn-default" href="<?= base_url('propositions/file-dg') ?>"><i class="fa fa-gavel"></i> File DG</a>
        <?php endif; ?>
    </div>
</div>

<div class="app-card">
    <?php if (empty($propositions)): ?>
        <div class="empty-state"><i class="fa fa-handshake-o"></i>Aucune proposition. Créez-en depuis une candidature retenue.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Candidat</th><th>Poste</th><th>Éléments acceptés</th><th>Statut</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($propositions as $p): ?>
                    <?php $brouillon = $p['date_envoi'] === null; ?>
                    <tr>
                        <td><?= (int) $p['id'] ?></td>
                        <td><a href="<?= base_url('candidatures/' . $p['candidature_id']) ?>"><?= esc($p['prenom'] . ' ' . $p['nom']) ?></a><br><small class="text-muted"><?= esc($p['email']) ?></small></td>
                        <td><?= esc($p['poste']) ?><br><small class="text-muted"><?= esc($p['annonce']) ?></small></td>
                        <td><?= (int) $p['nb_regles'] ?> / <?= (int) $p['nb_elements'] ?></td>
                        <td><?= $brouillon ? tf_badge('Brouillon') : tf_statut_proposition($p['statut']) ?></td>
                        <td><?= tf_date($p['date_creation']) ?></td>
                        <td class="text-right">
                            <a class="btn btn-xs btn-warning" href="<?= base_url('propositions/' . $p['id']) ?>">Ouvrir</a>
                            <?php if ($brouillon && in_array($role, ['rh', 'dg'], true)): ?>
                                <a class="btn btn-xs btn-default" href="<?= base_url('propositions/' . $p['id'] . '/modifier') ?>">Modifier</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
