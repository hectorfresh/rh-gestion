<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-gavel text-warning"></i> File DG — contre-propositions</h1>
        <p class="page-subtitle">La décision du DG l’emporte toujours, même si le RH ou la finance a refusé.</p></div>
    <a href="<?= base_url('propositions') ?>" class="btn btn-default"><i class="fa fa-list"></i> Toutes les propositions</a>
</div>

<?php if (empty($contre_propositions)): ?>
    <div class="app-card"><div class="empty-state"><i class="fa fa-gavel"></i>Aucune contre-proposition en attente de décision DG.</div></div>
<?php else: ?>
    <?php foreach ($contre_propositions as $pe): ?>
        <?php $f = $pe['finance'] ?? []; ?>
        <div class="app-card">
            <div class="row">
                <div class="col-sm-7">
                    <h4 class="app-card-title"><?= esc($pe['libelle']) ?> — <?= esc($pe['prenom'] . ' ' . $pe['nom']) ?></h4>
                    <p class="text-muted"><?= esc($pe['poste']) ?> — <?= esc($pe['departement']) ?> · <?= esc($pe['annonce']) ?></p>
                    <p>
                        <strong>Offre initiale :</strong> <?= esc($pe['valeur_proposee']) ?> ·
                        <strong>Contre-proposition :</strong> <?= esc($pe['contre_valeur']) ?>
                    </p>
                    <p><strong>Motif du candidat :</strong> <?= esc($pe['motif_candidat']) ?></p>
                    <p>
                        <strong>Décision RH :</strong> <?= tf_decision($pe['rh_decision']) ?>
                        <?php if ($pe['rh_commentaire']): ?> <small>(<?= esc($pe['rh_commentaire']) ?>)</small><?php endif; ?>
                    </p>
                    <p>
                        <strong>Analyse finance :</strong> <?= tf_decision($pe['finance_decision']) ?>
                        <?php if ($pe['finance_commentaire']): ?> <small>(<?= esc($pe['finance_commentaire']) ?>)</small><?php endif; ?>
                    </p>
                </div>
                <div class="col-sm-5">
                    <?php if (! empty($f)): ?>
                        <table class="table table-condensed table-bordered">
                            <tr><th>Budget annuel</th><td class="money"><?= tf_money($f['budget_annuel'] ?? 0) ?></td></tr>
                            <tr><th>Masse salariale</th><td class="money"><?= tf_money($f['masse_salariale'] ?? 0) ?></td></tr>
                            <tr><th>Engagements</th><td class="money"><?= tf_money($f['engagements'] ?? 0) ?></td></tr>
                            <tr><th>Disponible hors poste</th><td class="money"><?= tf_money($f['budget_disponible'] ?? 0) ?></td></tr>
                            <?php if (isset($f['proposition_actuelle'], $f['budget_disponible_avec_poste'])): ?>
                                <tr><th>Poste actuel (annuel)</th><td class="money"><?= tf_money($f['proposition_actuelle']) ?></td></tr>
                                <tr><th>Disponible avec poste</th><td class="money"><?= tf_money($f['budget_disponible_avec_poste']) ?></td></tr>
                            <?php endif; ?>
                            <tr><th>Coût de la contre-proposition</th><td class="money"><?= tf_money($f['cout'] ?? 0) ?></td></tr>
                            <tr><th>Reste après coût</th><td class="money <?= (($f['reste_apres_cout'] ?? 0) < 0) ? 'text-danger' : 'text-success' ?>"><?= tf_money($f['reste_apres_cout'] ?? 0) ?></td></tr>
                            <?php if (isset($f['salaire_base'], $f['salaire_max'])): ?>
                                <tr><th>Fourchette du poste</th><td><?= tf_money($f['salaire_base']) ?> – <?= tf_money($f['salaire_max']) ?>
                                    <?php if (array_key_exists('dans_fourchette', $f)): ?>
                                        <br><?= $f['dans_fourchette'] ? '<span class="status-badge status-green">Dans la fourchette</span>' : '<span class="status-badge status-red">Hors fourchette</span>' ?>
                                    <?php endif; ?>
                                </td></tr>
                                <?php if (array_key_exists('budget_suffisant', $f)): ?>
                                    <tr><th>Budget</th><td><?= $f['budget_suffisant'] ? '<span class="status-badge status-green">Suffisant</span>' : '<span class="status-badge status-red">Insuffisant</span>' ?></td></tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">Aucun instantané financier disponible.</p>
                    <?php endif; ?>
                </div>
            </div>

            <hr>
            <form method="post" action="<?= base_url('propositions/elements/' . $pe['id'] . '/decider') ?>" class="form-inline">
                <?= csrf_field() ?>
                <input type="text" name="commentaire" class="form-control" placeholder="Motivation de la décision finale du DG">
                <button name="decision" value="accepte" class="btn btn-success"><i class="fa fa-gavel"></i> DG accepte la contre-proposition</button>
                <button name="decision" value="refuse" class="btn btn-danger"><i class="fa fa-gavel"></i> DG refuse</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?= $this->endSection() ?>
