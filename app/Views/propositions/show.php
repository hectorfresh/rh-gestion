<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<?php
$libellesStatut = [
    'en_cours'    => 'En cours',
    'en_attente'  => 'En attente',
    'confirmable' => 'Prête à confirmer',
    'confirmee'   => 'Confirmée',
    'abandonnee'  => 'Abandonnée',
];
$statut = $libellesStatut[$proposition['statut']] ?? $proposition['statut'];
$estBrouillon = $est_brouillon ?? ($proposition['date_envoi'] === null);
$peutModifier = $peut_modifier ?? false;
$peutConfirmer = $peut_confirmer ?? ($est_proprietaire && $proposition['statut'] === 'confirmable');
$placeholders = [
    'salaire'       => 'Nouveau salaire mensuel (ex. 600000)',
    'type_contrat'  => 'Nouveau type de contrat',
    'date_debut'    => 'Nouvelle date de début (AAAA-MM-JJ)',
    'horaires'      => 'Nouveaux horaires (ex. 08:00 - 16:00)',
    'periode_essai' => 'Nouvelle période d’essai (mois)',
];
?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-handshake-o text-warning"></i> Proposition #<?= (int) $proposition['id'] ?></h1>
        <p class="page-subtitle"><?= esc($proposition['prenom'] . ' ' . $proposition['nom']) ?> — <?= esc($proposition['poste']) ?> (<?= esc($proposition['departement']) ?>)</p>
    </div>
    <a href="<?= $est_proprietaire ? base_url('propositions/mes') : base_url('propositions') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<?php if ($estBrouillon): ?>
    <div class="alert alert-warning">
        <i class="fa fa-pencil"></i> <strong>Brouillon :</strong> les éléments ont été pré-remplis par l’application. Vérifiez le salaire, le contrat, la date et les horaires avant l’envoi au candidat.
        <?php if ($peutModifier): ?>
            <div style="margin-top:10px">
                <a class="btn btn-warning btn-sm" href="<?= base_url('propositions/' . $proposition['id'] . '/modifier') ?>"><i class="fa fa-pencil"></i> Modifier</a>
                <form method="post" action="<?= base_url('propositions/' . $proposition['id'] . '/envoyer') ?>" style="display:inline-block">
                    <?= csrf_field() ?>
                    <button class="btn btn-success btn-sm" onclick="return confirm('Envoyer cette proposition au candidat ?')"><i class="fa fa-send"></i> Envoyer au candidat</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="app-card">
    <div class="row">
        <div class="col-sm-8">
            <p><strong>Annonce :</strong> <?= esc($proposition['annonce']) ?></p>
            <p><strong>Fourchette du poste :</strong> <?= tf_money($proposition['salaire_base']) ?> – <?= tf_money($proposition['salaire_max']) ?></p>
            <p><strong>Statut :</strong> <?= $estBrouillon ? tf_badge('Brouillon') : tf_badge($statut) ?></p>
            <p><strong>Envoyée le :</strong> <?= $proposition['date_envoi'] ? tf_datetime($proposition['date_envoi']) : 'Pas encore envoyée' ?></p>
        </div>
        <div class="col-sm-4 text-right">
            <a class="btn btn-default btn-sm" href="<?= base_url('candidatures/' . $proposition['candidature_id']) ?>">Dossier candidat</a>
        </div>
    </div>
</div>

<?php if (! $estBrouillon && $proposition['statut'] === 'confirmable' && $est_proprietaire): ?>
    <div class="alert alert-success">
        <i class="fa fa-check-circle"></i> Tous les éléments sont acceptés. Vérifiez le récapitulatif puis confirmez : le contrat sera généré automatiquement.
    </div>
<?php elseif ($proposition['statut'] === 'confirmee'): ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> Proposition confirmée. Le contrat a été généré.</div>
<?php elseif ($proposition['statut'] === 'abandonnee'): ?>
    <div class="alert alert-danger"><i class="fa fa-times-circle"></i> Cette proposition a été abandonnée.</div>
<?php endif; ?>

<?php foreach ($elements as $element): ?>
    <?php
    $enAttente = $element['statut'] === 'propose' && $element['reponse'] === 'en_attente';
    $accepte = $element['statut'] === 'accepte';
    ?>
    <div class="app-card element-row">
        <div class="row">
            <div class="col-sm-7">
                <div class="element-libelle"><?= esc($element['libelle']) ?></div>
                <div class="element-valeur"><?= esc($element['valeur_proposee']) ?></div>
                <?php if ($element['contre_valeur']): ?>
                    <div class="text-muted" style="margin-top:6px">
                        Contre-proposition : <strong><?= esc($element['contre_valeur']) ?></strong>
                        <?php if ($element['motif_candidat']): ?>
                            <br><small>Motif : <?= esc($element['motif_candidat']) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($element['statut'] === 'contre_validee'): ?>
                    <div class="text-success" style="margin-top:6px"><strong>Valeur finale retenue : <?= esc($element['contre_valeur']) ?></strong></div>
                <?php endif; ?>
            </div>
            <div class="col-sm-5 text-right">
                <?php if ($accepte): ?>
                    <span class="status-badge status-green">Accepté</span>
                <?php elseif ($element['statut'] === 'contre_validee'): ?>
                    <span class="status-badge status-green">Contre-proposition acceptée</span>
                <?php elseif ($element['statut'] === 'contre_refusee'): ?>
                    <span class="status-badge status-red">Contre-proposition refusée</span>
                <?php elseif ($element['statut'] === 'contre_proposition'): ?>
                    <span class="status-badge status-orange">Contre-proposition en cours</span>
                <?php else: ?>
                    <span class="status-badge status-orange">En attente de votre réponse</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($est_proprietaire && ! $estBrouillon && ! in_array($proposition['statut'], ['confirmee', 'abandonnee'], true)): ?>
            <?php if ($enAttente): ?>
                <hr>
                <div class="row">
                    <div class="col-sm-5">
                        <form method="post" action="<?= base_url('propositions/elements/' . $element['id'] . '/repondre') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="reponse" value="oui">
                            <button class="btn btn-success btn-block"><i class="fa fa-check"></i> J’accepte</button>
                        </form>
                    </div>
                    <div class="col-sm-7">
                        <form method="post" action="<?= base_url('propositions/elements/' . $element['id'] . '/repondre') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="reponse" value="non">
                            <div class="form-group">
                                <input type="text" name="contre_valeur" class="form-control"
                                       placeholder="<?= esc($placeholders[$element['code']] ?? 'Ma contre-proposition') ?>" required>
                            </div>
                            <div class="form-group">
                                <input type="text" name="motif_candidat" class="form-control"
                                       placeholder="Motif de mon refus (obligatoire)" required>
                            </div>
                            <button class="btn btn-danger"><i class="fa fa-times"></i> Je refuse et propose</button>
                        </form>
                    </div>
                </div>
            <?php elseif ($element['statut'] === 'contre_refusee'): ?>
                <hr>
                <p class="text-muted">Votre contre-proposition a été refusée par le DG. Vous pouvez accepter l’offre initiale ou renoncer à la proposition.</p>
                <form method="post" action="<?= base_url('propositions/elements/' . $element['id'] . '/repondre') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="reponse" value="oui">
                    <button class="btn btn-success"><i class="fa fa-check"></i> J’accepte l’offre initiale (<?= esc($element['valeur_proposee']) ?>)</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (in_array($element['statut'], ['contre_proposition', 'contre_validee', 'contre_refusee'], true)): ?>
            <hr>
            <h5><i class="fa fa-random"></i> Circuit de la contre-proposition</h5>
            <div class="workflow-steps">
                <?php foreach (['rh' => 'RH', 'finance' => 'Finance (auto)', 'dg' => 'DG'] as $etape => $libelle): ?>
                    <?php $d = $element['decisions'][$etape] ?? null; ?>
                    <div class="workflow-step <?= esc($d['decision'] ?? 'en_attente') ?>">
                        <strong><?= $libelle ?></strong>
                        <?= tf_decision($d['decision'] ?? 'en_attente') ?>
                        <?php if (! empty($d['commentaire'])): ?><br><small><?= esc($d['commentaire']) ?></small><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($element['decisions'] as $etape => $d): ?>
                <?php if (! empty($d['details_decode'])): ?>
                    <?php $s = $d['details_decode']; ?>
                    <table class="table table-condensed table-bordered" style="margin-top:10px">
                        <tr><th>Budget annuel</th><td class="money"><?= tf_money($s['budget_annuel'] ?? 0) ?></td>
                            <th>Masse salariale</th><td class="money"><?= tf_money($s['masse_salariale'] ?? 0) ?></td></tr>
                        <tr><th>Engagements</th><td class="money"><?= tf_money($s['engagements'] ?? 0) ?></td>
                            <th>Coût évalué</th><td class="money"><?= tf_money($s['cout'] ?? 0) ?></td></tr>
                        <tr><th>Disponible</th><td class="money"><?= tf_money($s['budget_disponible'] ?? 0) ?></td>
                            <th>Reste</th><td class="money"><?= tf_money($s['reste_apres_cout'] ?? 0) ?></td></tr>
                    </table>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($element['peut_rh']): ?>
                <form method="post" action="<?= base_url('propositions/elements/' . $element['id'] . '/decider') ?>" class="form-inline">
                    <?= csrf_field() ?>
                    <input type="text" name="commentaire" class="form-control input-sm" placeholder="Commentaire RH">
                    <button name="decision" value="accepte" class="btn btn-sm btn-success"><i class="fa fa-check"></i> RH accepte</button>
                    <button name="decision" value="refuse" class="btn btn-sm btn-danger"><i class="fa fa-times"></i> RH refuse</button>
                </form>
            <?php endif; ?>

            <?php if ($element['peut_dg']): ?>
                <form method="post" action="<?= base_url('propositions/elements/' . $element['id'] . '/decider') ?>" class="form-inline">
                    <?= csrf_field() ?>
                    <input type="text" name="commentaire" class="form-control input-sm" placeholder="Décision finale DG">
                    <button name="decision" value="accepte" class="btn btn-sm btn-success"><i class="fa fa-gavel"></i> DG accepte</button>
                    <button name="decision" value="refuse" class="btn btn-sm btn-danger"><i class="fa fa-gavel"></i> DG refuse</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if ($peutConfirmer): ?>
    <div class="app-card text-center">
        <h4>Confirmer l’embauche</h4>
        <p class="text-muted">La confirmation génère automatiquement le contrat, la fiche salarié et l’onboarding.</p>
        <a class="btn btn-success btn-lg" href="<?= base_url('propositions/' . $proposition['id'] . '/confirmation') ?>">
            <i class="fa fa-check-circle"></i> Vérifier et confirmer
        </a>
    </div>
<?php endif; ?>

<?php if ($est_proprietaire && ! $estBrouillon && ! in_array($proposition['statut'], ['confirmee', 'abandonnee'], true)): ?>
    <div class="app-card">
        <form method="post" action="<?= base_url('propositions/' . $proposition['id'] . '/renoncer') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-default" onclick="return confirm('Renoncer à cette proposition ?')">
                <i class="fa fa-times"></i> Renoncer à la proposition
            </button>
        </form>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
