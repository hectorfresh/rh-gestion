<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<?php
$p = $progression;
$onboarding = $p['onboarding'];
$taches = $p['taches'];
?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-check-square-o text-warning"></i> Onboarding</h1>
        <p class="page-subtitle"><?= esc($salarie['prenom'] . ' ' . $salarie['nom']) ?> — <?= esc($salarie['poste']) ?></p>
    </div>
    <a href="<?= base_url('contrats/salarie/' . $salarie['id']) ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Frise des contrats</a>
</div>

<div class="app-card">
    <h4 class="app-card-title">Avancement</h4>
    <div class="progress">
        <div class="progress-bar progress-bar-success" style="width: <?= (int) $p['pourcentage'] ?>%"><?= (int) $p['pourcentage'] ?>%</div>
    </div>
    <p class="text-muted"><?= (int) $p['terminees'] ?> / <?= (int) $p['total'] ?> tâches terminées — statut : <?= tf_badge($onboarding['statut'] ?? 'a_faire') ?></p>
    <?php if (! empty($p['en_retard'])): ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> <?= count($p['en_retard']) ?> tâche(s) en retard.
        </div>
    <?php endif; ?>
</div>

<div class="app-card">
    <h4 class="app-card-title">Checklist d’intégration</h4>
    <?php if (empty($taches)): ?>
        <p class="text-muted">Aucune tâche d’onboarding.</p>
    <?php else: ?>
        <?php foreach ($taches as $tache): ?>
            <div class="element-row">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="element-libelle"><?= esc($tache['libelle']) ?></div>
                        <p class="text-muted" style="margin:4px 0 0"><?= esc($tache['description']) ?></p>
                        <small class="text-muted">
                            Échéance : <?= tf_date($tache['date_echeance']) ?>
                            <?= ! empty($tache['date_echeance']) && $tache['date_echeance'] < date('Y-m-d') && $tache['statut'] !== 'terminee' ? ' — <span class="text-danger">en retard</span>' : '' ?>
                        </small>
                    </div>
                    <div class="col-sm-4 text-right">
                        <form method="post" action="<?= base_url('contrats/onboarding/taches/' . $tache['id']) ?>" class="form-inline">
                            <?= csrf_field() ?>
                            <select name="statut" class="form-control input-sm">
                                <option value="a_faire" <?= $tache['statut'] === 'a_faire' ? 'selected' : '' ?>>À faire</option>
                                <option value="en_cours" <?= $tache['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="terminee" <?= $tache['statut'] === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                            </select>
                            <button class="btn btn-sm btn-success"><i class="fa fa-save"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="app-card">
    <h4 class="app-card-title">Documents d’intégration (GED)</h4>
    <p class="text-muted">Déposez les documents d’intégration dans le dossier du salarié.</p>
    <form method="post" action="<?= base_url('salaries/' . $salarie['id'] . '/upload') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-sm-4">
                <select name="type_document_id" class="form-control" required>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= (int) $type['id'] ?>"><?= esc($type['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4">
                <input type="file" name="fichier" class="form-control" required>
            </div>
            <div class="col-sm-4">
                <button class="btn btn-warning"><i class="fa fa-upload"></i> Déposer</button>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>