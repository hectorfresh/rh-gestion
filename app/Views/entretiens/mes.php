<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-calendar text-warning"></i> Mes entretiens</h1>
        <p class="page-subtitle">Confirmez votre présence ou signalez votre indisponibilité.</p></div>
    <a href="<?= base_url('candidatures/mes') ?>" class="btn btn-default"><i class="fa fa-file-text-o"></i> Mes candidatures</a>
</div>

<div class="app-card">
    <?php if (empty($entretiens)): ?>
        <div class="empty-state"><i class="fa fa-calendar-o"></i>Aucun entretien planifié.</div>
    <?php else: ?>
        <?php foreach ($entretiens as $e): ?>
            <div class="element-row">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="element-libelle"><?= esc($e['annonce']) ?> — <?= esc($e['poste']) ?></div>
                        <p class="text-muted" style="margin:5px 0 0">
                            <i class="fa fa-calendar"></i> <?= tf_datetime($e['date_entretien']) ?> ·
                            <i class="fa fa-map-marker"></i> <?= esc($e['lieu']) ?> ·
                            <i class="fa fa-tag"></i> <?= esc($e['type']) ?>
                        </p>
                        <?php if (! empty($e['intervieweur_nom'])): ?>
                            <p class="small text-muted">Intervieweur : <?= esc(trim($e['intervieweur_prenom'] . ' ' . $e['intervieweur_nom'])) ?></p>
                        <?php endif; ?>
                        <?php if ($e['commentaire']): ?><p class="small"><?= esc($e['commentaire']) ?></p><?php endif; ?>
                    </div>
                    <div class="col-sm-4 text-right">
                        <?= tf_badge($e['statut']) ?>
                        <?php if ($e['reponse_candidat'] === 'accepte'): ?>
                            <div class="text-success small"><i class="fa fa-check"></i> Présence confirmée</div>
                        <?php elseif ($e['reponse_candidat'] === 'refuse'): ?>
                            <div class="text-danger small"><i class="fa fa-times"></i> Convocation refusée</div>
                        <?php endif; ?>
                        <?php if ($e['note'] !== null): ?><div class="text-muted">Note : <?= number_format((float) $e['note'], 2, ',', ' ') ?>/20</div><?php endif; ?>
                    </div>
                </div>

                <?php if ($e['reponse_candidat'] === 'en_attente' && $e['statut'] === 'Planifié'): ?>
                    <hr>
                    <div class="row">
                        <div class="col-sm-6">
                            <form method="post" action="<?= base_url('entretiens/' . $e['id'] . '/repondre') ?>" class="form-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="reponse" value="accepte">
                                <button class="btn btn-success btn-sm"><i class="fa fa-check"></i> Confirmer ma présence</button>
                            </form>
                        </div>
                        <div class="col-sm-6">
                            <form method="post" action="<?= base_url('entretiens/' . $e['id'] . '/repondre') ?>" class="form-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="reponse" value="refuse">
                                <input type="text" name="motif" class="form-control input-sm" placeholder="Motif (facultatif)">
                                <button class="btn btn-danger btn-sm"><i class="fa fa-times"></i> Signaler une indisponibilité</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
