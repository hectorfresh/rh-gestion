<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<?php
$auteur = static function (?array $d): string {
    if (! $d) {
        return '—';
    }
    $nom = trim(($d['valideur_prenom'] ?? '') . ' ' . ($d['valideur_nom'] ?? ''));
    if ($nom !== '') {
        return $nom;
    }
    return (int) ($d['automatique'] ?? 0) === 1 ? 'Système (automatique)' : '—';
};
$etapes = [
    'rh'      => ['libelle' => 'Validation RH', 'icone' => 'fa-user-check'],
    'finance' => ['libelle' => 'Finance (automatique)', 'icone' => 'fa-calculator'],
    'dg'      => ['libelle' => 'Décision finale DG', 'icone' => 'fa-gavel'],
];
?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-clipboard text-warning"></i> Besoin #<?= (int) $besoin['id'] ?></h1>
        <p class="page-subtitle">
            <?= esc($besoin['poste']) ?> — <?= esc($besoin['departement']) ?>
            &nbsp; <?= tf_badge($besoin['statut']) ?>
        </p>
    </div>
    <a href="<?= base_url('besoins') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="app-card">
            <h4 class="app-card-title">Informations du besoin</h4>
            <table class="table table-condensed">
                <tr><th style="width:180px">Statut</th><td><?= tf_badge($besoin['statut']) ?></td></tr>
                <tr><th>Poste</th><td><?= esc($besoin['poste']) ?></td></tr>
                <tr><th>Département</th><td><?= esc($besoin['departement']) ?></td></tr>
                <tr><th>Demandeur</th><td><?= esc(($besoin['demandeur_prenom'] ?? '') . ' ' . ($besoin['demandeur_nom'] ?? '')) ?> (<?= esc($besoin['demandeur_email']) ?>)</td></tr>
                <tr><th>Nombre de postes</th><td><?= (int) $besoin['nombre'] ?> — <?= (int) $embauches ?> retenu(s) / embauché(s)</td></tr>
                <tr><th>Type</th><td><?= esc($besoin['type_besoin'] === 'remplacement' ? 'Remplacement' : 'Création') ?></td></tr>
                <tr><th>Salaire estimé</th><td class="money"><?= tf_money($besoin['salaire_estime']) ?> / mois</td></tr>
                <tr><th>Fourchette du poste</th><td class="money"><?= tf_money($besoin['salaire_base']) ?> – <?= tf_money($besoin['salaire_max']) ?></td></tr>
                <tr><th>Coût annuel évalué</th><td class="money"><strong><?= tf_money($besoin['cout'] ?? 0) ?></strong></td></tr>
                <tr><th>Date souhaitée</th><td><?= tf_date($besoin['date_souhaitee']) ?></td></tr>
                <tr><th>Créé le</th><td><?= tf_datetime($besoin['date_creation']) ?></td></tr>
                <?php if (! empty($besoin['date_cloture'])): ?>
                    <tr><th>Clôturé le</th><td><?= tf_datetime($besoin['date_cloture']) ?></td></tr>
                <?php endif; ?>
                <tr><th>Motif</th><td><?= esc($besoin['motif'] ?: '—') ?></td></tr>
                <tr><th>Description</th><td><?= nl2br(esc($besoin['description'] ?: '—')) ?></td></tr>
            </table>
        </div>

        <div class="app-card">
            <h4 class="app-card-title"><i class="fa fa-columns"></i> Comparatif des décisions</h4>
            <div class="row">
                <?php foreach ($etapes as $etape => $meta): ?>
                    <?php $d = $decisions[$etape] ?? null; ?>
                    <div class="col-md-4">
                        <div class="workflow-step <?= esc($d['decision'] ?? 'en_attente') ?>" style="min-height:130px">
                            <strong><i class="fa <?= $meta['icone'] ?>"></i> <?= esc($meta['libelle']) ?></strong>
                            <div style="margin:6px 0"><?= tf_decision($d['decision'] ?? 'en_attente') ?></div>
                            <small class="text-muted"><?= esc($auteur($d)) ?></small>
                            <?php if (! empty($d['date_decision'])): ?>
                                <small class="text-muted"><?= tf_datetime($d['date_decision']) ?></small>
                            <?php endif; ?>
                            <?php if (! empty($d['commentaire'])): ?>
                                <small class="text-muted">« <?= esc($d['commentaire']) ?> »</small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($finance): ?>
                <h5 style="margin-top:22px"><i class="fa fa-calculator"></i> Instantané finance enregistré à la soumission</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>Budget annuel</th>
                                <th>Masse salariale</th>
                                <th>Engagements</th>
                                <th>Coût du besoin</th>
                                <th>Budget disponible</th>
                                <th>Reste après coût</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="money"><?= tf_money($finance['budget_annuel'] ?? 0) ?></td>
                                <td class="money"><?= tf_money($finance['masse_salariale'] ?? 0) ?></td>
                                <td class="money"><?= tf_money($finance['engagements'] ?? 0) ?></td>
                                <td class="money"><?= tf_money($finance['cout'] ?? 0) ?></td>
                                <td class="money"><?= tf_money($finance['budget_disponible'] ?? 0) ?></td>
                                <td class="money <?= ($finance['reste_apres_cout'] ?? 0) < 0 ? 'text-danger' : 'text-success' ?>">
                                    <strong><?= tf_money($finance['reste_apres_cout'] ?? 0) ?></strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small">
                    Formule : <?= esc($finance['formule'] ?? 'nombre × salaire estimé × 12') ?>.
                    L’étape finance est calculée automatiquement dès la soumission.
                </p>
            <?php else: ?>
                <p class="text-muted">Aucun instantané finance n’est encore enregistré.</p>
            <?php endif; ?>

            <?php if ($peut_rh): ?>
                <hr>
                <h5><i class="fa fa-user-check"></i> Décision RH</h5>
                <p class="text-muted small">
                    Le refus RH exige un commentaire. La décision finale reste celle du DG.
                </p>
                <form method="post" action="<?= base_url('besoins/' . $besoin['id'] . '/decider') ?>" data-decision-form>
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <textarea name="commentaire" class="form-control" rows="2"
                                  placeholder="Commentaire (obligatoire en cas de refus)"></textarea>
                    </div>
                    <button name="decision" value="accepte" class="btn btn-success"><i class="fa fa-check"></i> Valider (RH)</button>
                    <button name="decision" value="refuse" class="btn btn-danger"><i class="fa fa-times"></i> Refuser (RH)</button>
                </form>
            <?php endif; ?>

            <?php if ($peut_dg): ?>
                <hr>
                <h5><i class="fa fa-gavel"></i> Décision finale du DG</h5>
                <p class="text-muted small">
                    Le DG voit le RH et la finance côte à côte. Sa décision est <strong>finale</strong> :
                    elle l’emporte même si RH et/ou finance ont refusé.
                </p>
                <form method="post" action="<?= base_url('besoins/' . $besoin['id'] . '/decider') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <textarea name="commentaire" class="form-control" rows="2"
                                  placeholder="Motivation de la décision finale"></textarea>
                    </div>
                    <button name="decision" value="accepte" class="btn btn-success"><i class="fa fa-check"></i> Valider définitivement</button>
                    <button name="decision" value="refuse" class="btn btn-danger"><i class="fa fa-times"></i> Refuser définitivement</button>
                </form>
            <?php endif; ?>

            <?php if ($role === 'dg' && $besoin['statut'] === 'En attente DG' && ! $peut_dg): ?>
                <p class="text-muted small" style="margin-top:12px">
                    <i class="fa fa-info-circle"></i> Vous ne pouvez pas décider sur votre propre demande.
                </p>
            <?php endif; ?>
        </div>

        <div class="app-card">
            <h4 class="app-card-title"><i class="fa fa-random"></i> Frise des décisions</h4>
            <ul class="timeline">
                <?php foreach ($etapes as $etape => $meta): ?>
                    <?php $d = $decisions[$etape] ?? null; ?>
                    <li>
                        <strong><i class="fa <?= $meta['icone'] ?>"></i> <?= esc($meta['libelle']) ?></strong>
                        <?= tf_decision($d['decision'] ?? 'en_attente') ?>
                        <small>
                            <?= esc($auteur($d)) ?>
                            <?php if (! empty($d['date_decision'])): ?> — <?= tf_datetime($d['date_decision']) ?><?php endif; ?>
                        </small>
                        <?php if (! empty($d['commentaire'])): ?>
                            <small>« <?= esc($d['commentaire']) ?> »</small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5 style="margin-top:20px">Historique des statuts</h5>
            <ul class="timeline">
                <?php foreach ($historique as $h): ?>
                    <li>
                        <strong><?= esc($h['libelle'] ?? ('Statut #' . $h['statut_id'])) ?></strong>
                        <small>
                            <?= tf_datetime($h['date_changement']) ?>
                            <?php if (! empty($h['utilisateur_nom'])): ?>
                                — <?= esc(($h['utilisateur_prenom'] ?? '') . ' ' . $h['utilisateur_nom']) ?>
                            <?php endif; ?>
                        </small>
                        <?php if (! empty($h['commentaire'])): ?>
                            <small><?= esc($h['commentaire']) ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <div class="app-card">
            <h4 class="app-card-title"><i class="fa fa-info-circle"></i> Circuit</h4>
            <p class="text-muted small">
                Soumission → <strong>En attente RH</strong> → décision RH → <strong>En attente DG</strong>
                → décision finale du DG (<strong>Validé</strong> ou <strong>Refusé</strong>).
            </p>
            <p class="text-muted small">
                La finance est calculée automatiquement à la soumission et conservée dans
                <code>workflow_validation.details_json</code>.
            </p>
        </div>

        <?php if ($peut_creer_annonce): ?>
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-bullhorn"></i> Publier une annonce</h4>
                <p class="text-muted small">Une seule annonce est autorisée par besoin validé.</p>
                <form method="post" action="<?= base_url('besoins/' . $besoin['id'] . '/annonce') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Titre</label>
                        <input type="text" name="titre" class="form-control" value="Recrutement : <?= esc($besoin['poste']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= esc($besoin['description']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Profil recherché</label>
                        <textarea name="profil" class="form-control" rows="3" placeholder="Diplômes, compétences, expérience…"><?= esc($besoin['description']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Date limite</label>
                        <input type="date" name="date_limite" class="form-control">
                    </div>
                    <button class="btn btn-warning btn-block"><i class="fa fa-bullhorn"></i> Publier</button>
                </form>
            </div>
        <?php elseif (in_array($role, ['rh', 'dg'], true) && $besoin['statut'] === 'Validé' && ! empty($annonces)): ?>
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-bullhorn"></i> Annonce liée</h4>
                <p class="text-muted small">Ce besoin possède déjà son annonce.</p>
                <a class="btn btn-default btn-block" href="<?= base_url('admin/annonces/' . $annonces[0]['id'] . '/modifier') ?>">
                    <i class="fa fa-pencil"></i> Modifier l’annonce
                </a>
            </div>
        <?php endif; ?>

        <div class="app-card">
            <h4 class="app-card-title"><i class="fa fa-bullhorn"></i> Annonces liées</h4>
            <?php if (empty($annonces)): ?>
                <p class="text-muted">Aucune annonce.</p>
            <?php else: ?>
                <?php foreach ($annonces as $a): ?>
                    <p>
                        <a href="<?= base_url('annonces/' . $a['id']) ?>"><?= esc($a['titre']) ?></a><br>
                        <?= tf_badge((int) $a['est_cloture'] === 1 ? 'Clôturée' : 'Ouverte') ?>
                        <small class="text-muted"><?= tf_date($a['date_publication']) ?></small>
                    </p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('[data-decision-form]').forEach(function (form) {
        var commentaire = form.querySelector('textarea[name="commentaire"]');
        form.querySelectorAll('button[name="decision"]').forEach(function (bouton) {
            bouton.addEventListener('click', function (event) {
                var refus = bouton.value === 'refuse';
                commentaire.required = refus;
                if (refus && commentaire.value.trim() === '') {
                    event.preventDefault();
                    commentaire.focus();
                    alert('Un commentaire est obligatoire pour refuser le besoin.');
                }
            });
        });
    });
})();
</script>
<?= $this->endSection() ?>
