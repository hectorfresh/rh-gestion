<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<?php
$propositionVisible = $proposition && (! $est_proprietaire || $proposition['date_envoi'] !== null);
$propositionBrouillon = $proposition && $proposition['date_envoi'] === null;
?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-file-text-o text-warning"></i> Candidature #<?= (int) $candidature['id'] ?></h1>
        <p class="page-subtitle"><?= esc($candidature['prenom'] . ' ' . $candidature['nom']) ?> — <?= esc($candidature['annonce']) ?></p>
    </div>
    <a href="<?= $est_proprietaire ? base_url('candidatures/mes') : base_url('candidatures') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="app-card">
            <h4 class="app-card-title">Candidat</h4>
            <table class="table table-condensed">
                <tr><th style="width:180px">Nom</th><td><?= esc($candidature['prenom'] . ' ' . $candidature['nom']) ?></td></tr>
                <tr><th>Email</th><td><?= esc($candidature['email']) ?></td></tr>
                <tr><th>Téléphone</th><td><?= esc($identite['telephone'] ?? $candidature['telephone'] ?: '—') ?></td></tr>
                <tr><th>Ville</th><td><?= esc($identite['ville'] ?? '—') ?></td></tr>
                <tr><th>Statut</th><td><?= tf_badge($candidature['statut']) ?></td></tr>
                <tr><th>Poste visé</th><td><?= esc($candidature['poste']) ?> (<?= esc($candidature['departement']) ?>)</td></tr>
                <tr><th>Date de candidature</th><td><?= tf_datetime($candidature['date_candidature']) ?></td></tr>
            </table>
            <?php if ($candidature['lettre_motivation']): ?>
                <h5>Lettre de motivation</h5>
                <p><?= nl2br(esc($candidature['lettre_motivation'])) ?></p>
            <?php endif; ?>
        </div>

        <div class="app-card">
            <h4 class="app-card-title"><i class="fa fa-file-text"></i> CV — <?= esc($cv['titre'] ?? 'Non renseigné') ?></h4>
            <?php if (! $cv): ?>
                <p class="text-muted">Aucun CV.</p>
            <?php else: ?>
                <p><?= nl2br(esc($cv['descri'] ?? '')) ?></p>
                <?php if ($formations): ?>
                    <h5>Formations</h5>
                    <ul>
                        <?php foreach ($formations as $f): ?>
                            <li><?= esc($f['diplome'] ?: 'Diplôme') ?> — <?= esc($f['etablissement'] ?: '') ?>
                                <small class="text-muted">(<?= tf_date($f['date_debut']) ?> – <?= tf_date($f['date_fin']) ?>)</small></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($experiences): ?>
                    <h5>Expériences</h5>
                    <ul>
                        <?php foreach ($experiences as $e): ?>
                            <li><strong><?= esc($e['poste'] ?: 'Poste') ?></strong> — <?= esc($e['entreprise'] ?: '') ?>
                                <small class="text-muted">(<?= tf_date($e['date_debut']) ?> – <?= tf_date($e['date_fin']) ?>)</small>
                                <?php if ($e['description']): ?><br><?= esc($e['description']) ?><?php endif; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (! empty($langues)): ?>
                    <h5>Langues</h5>
                    <ul>
                        <?php foreach ($langues as $l): ?>
                            <li><?= esc($l['langue']) ?> — <?= esc($l['niveau']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="app-card">
            <h4 class="app-card-title"><i class="fa fa-calendar"></i> Entretiens</h4>
            <?php if (empty($entretiens)): ?>
                <p class="text-muted">Aucun entretien planifié.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-condensed">
                        <thead><tr><th>Date</th><th>Type</th><th>Lieu</th><th>Statut</th><th>Réponse candidat</th><th>Note</th><th>Intervieweur</th></tr></thead>
                        <tbody>
                        <?php foreach ($entretiens as $e): ?>
                            <tr>
                                <td><?= tf_datetime($e['date_entretien']) ?></td>
                                <td><?= esc($e['type']) ?></td>
                                <td><?= esc($e['lieu']) ?></td>
                                <td><?= tf_badge($e['statut']) ?></td>
                                <td>
                                    <?php if ($e['reponse_candidat'] === 'accepte'): ?>
                                        <?= tf_badge('Accepté') ?>
                                    <?php elseif ($e['reponse_candidat'] === 'refuse'): ?>
                                        <?= tf_badge('Refusé') ?>
                                    <?php else: ?>
                                        <?= tf_badge('En attente') ?>
                                    <?php endif; ?>
                                    <?php if (! empty($e['motif_reponse'])): ?><br><small class="text-muted"><?= esc($e['motif_reponse']) ?></small><?php endif; ?>
                                </td>
                                <td><?= $e['note'] !== null ? number_format((float) $e['note'], 2, ',', ' ') . '/20' : '—' ?></td>
                                <td><?= esc(trim(($e['intervieweur_prenom'] ?? '') . ' ' . ($e['intervieweur_nom'] ?? '')) ?: '—') ?></td>
                            </tr>
                            <?php if ($staff && can_show('entretiens', 'evaluer') && $e['statut'] === 'Planifié'): ?>
                                <tr><td colspan="7">
                                    <form method="post" action="<?= base_url('entretiens/' . $e['id'] . '/evaluer') ?>" class="form-inline">
                                        <?= csrf_field() ?>
                                        <input type="number" name="note" step="0.01" min="0" max="20" class="form-control input-sm" placeholder="Note /20" required>
                                        <select name="decision" class="form-control input-sm">
                                            <option value="retenue">Retenir</option>
                                            <option value="refusee">Refuser</option>
                                            <option value="reserve">Réserver</option>
                                        </select>
                                        <input type="text" name="commentaire" class="form-control input-sm" placeholder="Appréciation">
                                        <button class="btn btn-sm btn-warning">Évaluer</button>
                                    </form>
                                </td></tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($evaluations): ?>
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-star"></i> Évaluations</h4>
                <?php foreach ($evaluations as $ev): ?>
                    <p><strong>Évaluation</strong> : <?= number_format((float) $ev['note'], 2, ',', ' ') ?>/20
                        <?= tf_badge($ev['decision'] ?? 'reserve') ?>
                        <?php if ($ev['appreciation']): ?><br><small class="text-muted"><?= esc($ev['appreciation']) ?></small><?php endif; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (! empty($historique)): ?>
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-history"></i> Historique des statuts</h4>
                <ul class="timeline">
                    <?php foreach ($historique as $h): ?>
                        <li>
                            <strong><?= esc($h['statut']) ?></strong>
                            <small><?= tf_datetime($h['date_changement']) ?>
                                <?php if (! empty($h['nom'])): ?> — <?= esc(trim($h['prenom'] . ' ' . $h['nom'])) ?><?php endif; ?>
                            </small>
                            <?php if (! empty($h['commentaire'])): ?><small><?= esc($h['commentaire']) ?></small><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($propositionVisible): ?>
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-handshake-o"></i> Proposition d’embauche</h4>
                <p>Statut : <?= $propositionBrouillon ? tf_badge('Brouillon') : tf_statut_proposition($proposition['statut']) ?></p>
                <table class="table table-condensed">
                    <thead><tr><th>Élément</th><th>Proposé</th><th>Réponse</th><th>Contre-proposition</th></tr></thead>
                    <tbody>
                    <?php foreach ($elements as $el): ?>
                        <tr>
                            <td><?= esc($el['libelle']) ?></td>
                            <td><?= esc($el['valeur_proposee']) ?></td>
                            <td><?= $el['reponse'] === 'oui' ? '<span class="status-badge status-green">OUI</span>' : ($el['reponse'] === 'non' ? '<span class="status-badge status-red">NON</span>' : '<span class="status-badge status-orange">En attente</span>') ?></td>
                            <td>
                                <?php if ($el['contre_valeur']): ?>
                                    <?= esc($el['contre_valeur']) ?> — <?= tf_statut_element($el['statut'], $el['reponse']) ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a class="btn btn-warning" href="<?= base_url('propositions/' . $proposition['id']) ?>">
                    <i class="fa fa-handshake-o"></i> <?= $propositionBrouillon ? 'Vérifier et envoyer la proposition' : ($est_proprietaire ? 'Gérer ma proposition' : 'Voir la proposition') ?>
                </a>
                <?php if ($propositionBrouillon): ?>
                    <a class="btn btn-default" href="<?= base_url('propositions/' . $proposition['id'] . '/modifier') ?>"><i class="fa fa-pencil"></i> Modifier</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($contrat): ?>
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-file-text-o"></i> Contrat généré</h4>
                <p>Contrat #<?= (int) $contrat['id'] ?> — <?= esc($contrat['type_contrat']) ?> —
                    <?= tf_badge($contrat['statut']) ?></p>
                <a class="btn btn-default" href="<?= base_url('contrats/' . $contrat['id']) ?>">Voir le contrat</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <?php if (can_show('candidatures', 'modifier')): ?>
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-cogs"></i> Actions RH</h4>
                <form method="post" action="<?= base_url('candidatures/' . $candidature['id'] . '/statut') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Changer le statut</label>
                        <select name="statut" class="form-control">
                            <?php foreach (['Reçue','Présélectionnée','En attente entretien','Retenue','Non retenue'] as $s): ?>
                                <option value="<?= $s ?>" <?= $candidature['statut'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="help-block">Le statut « Retenue » prépare automatiquement un brouillon de proposition d’embauche.</p>
                    </div>
                    <div class="form-group"><input type="text" name="commentaire" class="form-control" placeholder="Commentaire (facultatif)"></div>
                    <button class="btn btn-default btn-block">Mettre à jour</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (can_show('entretiens', 'creer')): ?>
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-calendar-plus-o"></i> Planifier un entretien</h4>
                <form method="post" action="<?= base_url('candidatures/' . $candidature['id'] . '/entretien') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group"><label>Date et heure</label>
                        <input type="datetime-local" name="date_entretien" class="form-control" required></div>
                    <div class="form-group"><label>Type</label>
                        <select name="type" class="form-control">
                            <option value="RH">RH</option>
                            <option value="Technique">Technique</option>
                            <option value="Manager">Manager</option>
                            <option value="Final">Final</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Lieu</label>
                        <input type="text" name="lieu" class="form-control" required></div>
                    <div class="form-group"><label>Intervieweur</label>
                        <select name="intervieweur_id" class="form-control">
                            <?php foreach ($intervieweurs as $i): ?>
                                <option value="<?= (int) $i['id'] ?>" <?= (int) $i['id'] === (int) $utilisateur['id'] ? 'selected' : '' ?>>
                                    <?= esc($i['prenom'] . ' ' . $i['nom']) ?> (<?= esc($i['role']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Commentaire</label>
                        <textarea name="commentaire" class="form-control" rows="2"></textarea></div>
                    <button class="btn btn-warning btn-block">Planifier</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (can_show('propositions', 'creer') && ! $proposition && $candidature['statut'] === 'Retenue'): ?>
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-handshake-o"></i> Proposition d’embauche</h4>
                <p class="text-muted small">La proposition est créée automatiquement lors du passage à « Retenue ». Vous pouvez aussi la vérifier ou la compléter.</p>
                <a class="btn btn-warning btn-block" href="<?= base_url('candidatures/' . $candidature['id'] . '/proposition') ?>">Créer la proposition</a>
            </div>
        <?php endif; ?>

        <?php if ($est_proprietaire && $proposition && $proposition['date_envoi'] !== null): ?>
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-handshake-o"></i> Ma proposition</h4>
                <p>Consultez et répondez aux éléments proposés.</p>
                <a class="btn btn-warning btn-block" href="<?= base_url('propositions/' . $proposition['id']) ?>">Ouvrir</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
