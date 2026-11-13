<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<?php
$mode = $mode ?? 'create';
$proposition = $proposition ?? null;
$elements = $elements ?? [];
$salaire = old('salaire', $elements['salaire']['valeur_proposee'] ?? $salaire_defaut);
$typeContrat = old('type_contrat', $elements['type_contrat']['valeur_proposee'] ?? 'CDI');
$dateDebut = old('date_debut', $elements['date_debut']['valeur_proposee'] ?? $date_defaut);
$horaires = $elements['horaires']['valeur_proposee'] ?? '08:00 - 17:00';
[$heureDebut, $heureFin] = array_map('trim', array_pad(preg_split('/\s*-\s*/', $horaires), 2, ''));
$heureDebut = old('heure_debut', $heureDebut ?: '08:00');
$heureFin = old('heure_fin', $heureFin ?: '17:00');
$essai = old('periode_essai', $elements['periode_essai']['valeur_proposee'] ?? '3');
$action = $mode === 'edit'
    ? base_url('propositions/' . (int) $proposition['id'] . '/modifier')
    : base_url('candidatures/' . (int) $candidature['id'] . '/proposition');
?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-handshake-o text-warning"></i> <?= $mode === 'edit' ? 'Modifier la proposition' : 'Proposition d’embauche' ?></h1>
        <p class="page-subtitle"><?= esc($candidature['prenom'] . ' ' . $candidature['nom']) ?> — <?= esc($candidature['poste']) ?></p>
    </div>
    <a href="<?= base_url($mode === 'edit' ? 'propositions/' . (int) $proposition['id'] : 'candidatures/' . (int) $candidature['id']) ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<?php if ($mode === 'edit'): ?>
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i> Cette proposition est un brouillon. Ajustez les valeurs dans la fourchette du poste, puis enregistrez ou envoyez-la au candidat.
    </div>
<?php endif; ?>

<div class="app-card">
    <h4 class="app-card-title">Éléments proposés au candidat</h4>
    <p class="text-muted">Chaque élément doit recevoir une réponse OUI ou NON. Un NON ouvre une contre-proposition qui suit le circuit RH → finance → DG.</p>

    <form method="post" action="<?= $action ?>">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Salaire mensuel proposé <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="<?= esc($candidature['salaire_base']) ?>" max="<?= esc($candidature['salaire_max']) ?>"
                           name="salaire" class="form-control" value="<?= esc($salaire) ?>" required>
                    <small class="help-block">Fourchette : <?= tf_money($candidature['salaire_base']) ?> – <?= tf_money($candidature['salaire_max']) ?></small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Type de contrat <span class="text-danger">*</span></label>
                    <select name="type_contrat" class="form-control">
                        <?php foreach (['CDI', 'CDD', 'Essai', 'Stage', 'Alternance'] as $type): ?>
                            <option value="<?= esc($type) ?>" <?= $typeContrat === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="form-group"><label>Date de début <span class="text-danger">*</span></label>
                    <input type="date" name="date_debut" class="form-control" value="<?= esc($dateDebut) ?>" required></div>
            </div>
            <div class="col-sm-4">
                <div class="form-group"><label>Heure de début</label>
                    <input type="time" name="heure_debut" class="form-control" value="<?= esc($heureDebut) ?>"></div>
            </div>
            <div class="col-sm-4">
                <div class="form-group"><label>Heure de fin</label>
                    <input type="time" name="heure_fin" class="form-control" value="<?= esc($heureFin) ?>"></div>
            </div>
        </div>

        <div class="form-group">
            <label>Période d’essai (mois)</label>
            <input type="number" name="periode_essai" class="form-control" min="0" max="12" value="<?= esc($essai) ?>">
        </div>

        <div class="form-actions text-right">
            <a href="<?= base_url($mode === 'edit' ? 'propositions/' . (int) $proposition['id'] : 'candidatures/' . (int) $candidature['id']) ?>" class="btn btn-default">Annuler</a>
            <?php if ($mode === 'edit'): ?>
                <button name="envoyer" value="0" class="btn btn-default"><i class="fa fa-save"></i> Enregistrer le brouillon</button>
                <button name="envoyer" value="1" class="btn btn-warning"><i class="fa fa-send"></i> Enregistrer et envoyer</button>
            <?php else: ?>
                <button class="btn btn-warning"><i class="fa fa-send"></i> Envoyer la proposition</button>
            <?php endif; ?>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
