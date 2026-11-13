<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-plane text-warning"></i> Demande de congé #<?= (int) $demande['id'] ?></h1>
        <p class="page-subtitle"><?= esc($demande['prenom'] . ' ' . $demande['nom']) ?> — <?= esc($demande['type']) ?></p>
    </div>
    <a href="<?= $est_proprietaire ? base_url('conges/mes') : base_url('conges/validation') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="app-card">
            <h4 class="app-card-title">Détail de la demande</h4>
            <table class="table table-condensed">
                <tr><th style="width:180px">Statut</th><td><?= tf_badge($demande['statut']) ?></td></tr>
                <tr><th>Type</th><td><?= esc($demande['type']) ?></td></tr>
                <tr><th>Du</th><td><?= tf_date($demande['date_debut']) ?></td></tr>
                <tr><th>Au</th><td><?= tf_date($demande['date_fin']) ?></td></tr>
                <tr><th>Jours ouvrables</th><td><?= number_format((float) $demande['nombre_jours'], 1, ',', ' ') ?></td></tr>
                <tr><th>Demandée le</th><td><?= tf_datetime($demande['date_demande']) ?></td></tr>
                <tr><th>Motif</th><td><?= nl2br(esc($demande['motif'] ?: '—')) ?></td></tr>
                <tr><th>Justificatif</th><td><?= esc($demande['justificatif'] ?: '—') ?></td></tr>
                <tr><th>Validation manager</th><td><?= $demande['valide_manager_par'] ? esc(trim(($demande['manager_prenom'] ?? '') . ' ' . ($demande['manager_nom'] ?? ''))) . ' le ' . tf_datetime($demande['date_validation_manager']) : '—' ?></td></tr>
                <tr><th>Traitement RH</th><td><?= $demande['traite_par'] ? esc(trim(($demande['rh_prenom'] ?? '') . ' ' . ($demande['rh_nom'] ?? ''))) . ' le ' . tf_datetime($demande['date_traitement']) : '—' ?></td></tr>
                <tr><th>Commentaire</th><td><?= nl2br(esc($demande['commentaire_validation'] ?: '—')) ?></td></tr>
            </table>

            <div class="alert alert-info">
                Solde <?= esc($demande['type']) ?> <?= date('Y', strtotime($demande['date_debut'])) ?> :
                <strong><?= number_format((float) $solde['disponible'], 1, ',', ' ') ?> jour(s)</strong> disponible(s)
                (<?= number_format((float) $solde['jours_acquis'], 1, ',', ' ') ?> acquis − <?= number_format((float) $solde['jours_pris'], 1, ',', ' ') ?> pris).
            </div>

            <?php if ($peut_manager): ?>
                <hr>
                <h5><i class="fa fa-check-square"></i> Validation manager</h5>
                <form method="post" action="<?= base_url('conges/' . $demande['id'] . '/manager') ?>" class="form-inline">
                    <?= csrf_field() ?>
                    <input type="text" name="commentaire" class="form-control" placeholder="Commentaire (facultatif)">
                    <button class="btn btn-success"><i class="fa fa-check"></i> Valider (manager)</button>
                </form>
            <?php endif; ?>

            <?php if ($peut_rh): ?>
                <hr>
                <h5><i class="fa fa-user-check"></i> Validation finale RH</h5>
                <form method="post" action="<?= base_url('conges/' . $demande['id'] . '/rh') ?>" class="form-inline">
                    <?= csrf_field() ?>
                    <input type="text" name="commentaire" class="form-control" placeholder="Commentaire (facultatif)">
                    <button class="btn btn-success"><i class="fa fa-check"></i> Accepter définitivement</button>
                </form>
            <?php endif; ?>

            <?php if ($peut_refuser): ?>
                <hr>
                <form method="post" action="<?= base_url('conges/' . $demande['id'] . '/refuser') ?>" class="form-inline">
                    <?= csrf_field() ?>
                    <input type="text" name="commentaire" class="form-control" placeholder="Motif du refus" required>
                    <button class="btn btn-danger"><i class="fa fa-times"></i> Refuser</button>
                </form>
            <?php endif; ?>

            <?php if ($est_proprietaire && in_array($demande['statut'], ['En attente', 'Validé manager'], true)): ?>
                <hr>
                <form method="post" action="<?= base_url('conges/' . $demande['id'] . '/annuler') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-default" onclick="return confirm('Annuler cette demande ?')"><i class="fa fa-times"></i> Annuler ma demande</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-5">
        <div class="app-card">
            <h4 class="app-card-title">Circuit</h4>
            <div class="workflow-steps" style="flex-direction:column">
                <div class="workflow-step <?= $demande['statut'] === 'En attente' ? 'en_attente' : 'accepte' ?>">
                    <strong>1. Demande</strong> <?= tf_badge('En attente') ?>
                </div>
                <div class="workflow-step <?= $demande['statut'] === 'En attente' ? 'en_attente' : ($demande['statut'] === 'Refusé' ? 'refuse' : 'accepte') ?>">
                    <strong>2. Validation manager</strong>
                    <?= $demande['valide_manager_par'] ? tf_badge('Validé manager') : tf_badge('En attente') ?>
                </div>
                <div class="workflow-step <?= $demande['statut'] === 'Accepté' ? 'accepte' : ($demande['statut'] === 'Refusé' ? 'refuse' : 'en_attente') ?>">
                    <strong>3. Validation RH</strong>
                    <?= $demande['statut'] === 'Accepté' ? tf_badge('Accepté') : ($demande['statut'] === 'Refusé' ? tf_badge('Refusé') : tf_badge('En attente')) ?>
                </div>
            </div>
        </div>

        <div class="app-card">
            <h4 class="app-card-title">Historique</h4>
            <ul class="timeline">
                <?php foreach ($historique as $h): ?>
                    <li>
                        <strong><?= esc($h['libelle']) ?></strong>
                        <small><?= tf_datetime($h['date_changement']) ?>
                            <?= $h['prenom'] ? '— ' . esc($h['prenom'] . ' ' . $h['nom']) : '' ?></small>
                        <?php if ($h['commentaire']): ?><small><?= esc($h['commentaire']) ?></small><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
