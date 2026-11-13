<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-gavel text-danger"></i> File de décision finale — DG</h1>
        <p class="page-subtitle">
            Comparatif RH / finance pour chaque besoin. La décision du DG est finale et l’emporte
            sur les avis RH et finance.
        </p>
    </div>
    <a href="<?= base_url('besoins') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Tous les besoins</a>
</div>

<?php if (empty($besoins)): ?>
    <div class="app-card">
        <div class="empty-state"><i class="fa fa-check-circle"></i>Aucun besoin en attente de décision finale.</div>
    </div>
<?php else: ?>
    <?php foreach ($besoins as $b): ?>
        <?php
        $finance = $b['finance'] ?? [];
        $rh      = $b['rh'] ?? null;
        $dg      = $b['dg'] ?? null;
        ?>
        <div class="app-card">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="app-card-title">
                        #<?= (int) $b['id'] ?> — <?= esc($b['poste']) ?>
                        <small class="text-muted"><?= esc($b['departement']) ?></small>
                    </h4>
                    <p class="text-muted">
                        Demandeur : <?= esc(($b['demandeur_prenom'] ?? '') . ' ' . ($b['demandeur_nom'] ?? '')) ?> ·
                        <?= (int) $b['nombre'] ?> poste(s) ·
                        <?= esc(ucfirst($b['type_besoin'])) ?> ·
                        coût annuel <strong><?= tf_money($b['cout'] ?? 0) ?></strong>
                    </p>

                    <div class="row">
                        <div class="col-sm-4">
                            <div class="workflow-step <?= esc($rh['decision'] ?? 'en_attente') ?>">
                                <strong><i class="fa fa-user-check"></i> Avis RH</strong>
                                <?= tf_decision($rh['decision'] ?? 'en_attente') ?>
                                <?php if (! empty($rh['commentaire'])): ?>
                                    <small class="text-muted">« <?= esc($rh['commentaire']) ?> »</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="workflow-step <?= esc($b['decisions']['finance']['decision'] ?? 'en_attente') ?>">
                                <strong><i class="fa fa-calculator"></i> Finance (auto)</strong>
                                <?= tf_decision($b['decisions']['finance']['decision'] ?? 'en_attente') ?>
                                <small class="text-muted">Reste : <?= tf_money($finance['reste_apres_cout'] ?? 0) ?></small>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="workflow-step en_attente">
                                <strong><i class="fa fa-gavel"></i> DG</strong>
                                <?= tf_decision($dg['decision'] ?? 'en_attente') ?>
                                <small class="text-muted">Décision finale</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="table-responsive">
                        <table class="table table-condensed table-bordered">
                            <tr><th>Budget annuel</th><td class="money"><?= tf_money($finance['budget_annuel'] ?? 0) ?></td></tr>
                            <tr><th>Masse salariale</th><td class="money"><?= tf_money($finance['masse_salariale'] ?? 0) ?></td></tr>
                            <tr><th>Engagements</th><td class="money"><?= tf_money($finance['engagements'] ?? 0) ?></td></tr>
                            <tr><th>Coût du besoin</th><td class="money"><?= tf_money($finance['cout'] ?? 0) ?></td></tr>
                            <tr><th>Budget disponible</th><td class="money"><?= tf_money($finance['budget_disponible'] ?? 0) ?></td></tr>
                            <tr>
                                <th>Reste après coût</th>
                                <td class="money <?= ($finance['reste_apres_cout'] ?? 0) < 0 ? 'text-danger' : 'text-success' ?>">
                                    <strong><?= tf_money($finance['reste_apres_cout'] ?? 0) ?></strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <a href="<?= base_url('besoins/' . $b['id']) ?>" class="btn btn-danger btn-block">
                        <i class="fa fa-gavel"></i> Ouvrir et décider
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?= $this->endSection() ?>
