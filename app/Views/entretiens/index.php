<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-calendar text-warning"></i> Entretiens</h1>
        <p class="page-subtitle">Planification, réponse des candidats et évaluation.</p></div>
    <a href="<?= base_url('candidatures') ?>" class="btn btn-default"><i class="fa fa-file-text-o"></i> Candidatures</a>
</div>

<div class="app-card">
    <?php if (empty($entretiens)): ?>
        <div class="empty-state"><i class="fa fa-calendar-o"></i>Aucun entretien planifié.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Date</th><th>Candidat</th><th>Poste</th><th>Type</th><th>Lieu</th><th>Intervieweur</th><th>Réponse</th><th>Statut</th><th>Note</th><th>Évaluation</th></tr></thead>
                <tbody>
                <?php foreach ($entretiens as $e): ?>
                    <tr>
                        <td><?= (int) $e['id'] ?></td>
                        <td><?= tf_datetime($e['date_entretien']) ?></td>
                        <td><a href="<?= base_url('candidatures/' . $e['candidature_id']) ?>"><?= esc($e['prenom'] . ' ' . $e['nom']) ?></a></td>
                        <td><?= esc($e['poste']) ?><br><small class="text-muted"><?= esc($e['departement']) ?></small></td>
                        <td><?= esc($e['type']) ?></td>
                        <td><?= esc($e['lieu']) ?></td>
                        <td><?= esc(trim(($e['intervieweur_prenom'] ?? '') . ' ' . ($e['intervieweur_nom'] ?? '')) ?: '—') ?></td>
                        <td>
                            <?php if ($e['reponse_candidat'] === 'accepte'): ?><?= tf_badge('Accepté') ?>
                            <?php elseif ($e['reponse_candidat'] === 'refuse'): ?><?= tf_badge('Refusé') ?>
                            <?php else: ?><?= tf_badge('En attente') ?><?php endif; ?>
                            <?php if (! empty($e['motif_reponse'])): ?><br><small class="text-muted"><?= esc($e['motif_reponse']) ?></small><?php endif; ?>
                        </td>
                        <td><?= tf_badge($e['statut']) ?></td>
                        <td><?= $e['note'] !== null ? number_format((float) $e['note'], 2, ',', ' ') . '/20' : '—' ?></td>
                        <td>
                            <?php if ($e['statut'] === 'Planifié'): ?>
                                <form method="post" action="<?= base_url('entretiens/' . $e['id'] . '/evaluer') ?>" class="form-inline">
                                    <?= csrf_field() ?>
                                    <input type="number" name="note" step="0.01" min="0" max="20" class="form-control input-sm" placeholder="/20" style="width:70px" required>
                                    <select name="decision" class="form-control input-sm">
                                        <option value="retenue">Retenir</option>
                                        <option value="refusee">Refuser</option>
                                        <option value="reserve">Réserver</option>
                                    </select>
                                    <input type="text" name="commentaire" class="form-control input-sm" placeholder="Appréciation">
                                    <button class="btn btn-sm btn-warning">OK</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Évalué</span>
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
