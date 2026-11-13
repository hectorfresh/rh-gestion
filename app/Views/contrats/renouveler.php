<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-refresh text-warning"></i> Renouveler / modifier le contrat</h1>
        <p class="page-subtitle"><?= esc($salarie['prenom'] . ' ' . $salarie['nom']) ?> — ancien contrat #<?= (int) $ancien['id'] ?></p>
    </div>
    <a href="<?= base_url('contrats/salarie/' . $salarie['id']) ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<div class="app-card">
    <p class="text-muted">L’ancien contrat sera clôturé avec le statut « Remplacé » ; un nouveau contrat sera créé sans écraser l’historique.</p>

    <form method="post" action="<?= base_url('contrats/' . (int) $ancien['id'] . '/renouveler') ?>">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Type de contrat</label>
                    <select name="type_contrat" class="form-control">
                        <?php foreach (['Essai', 'CDD', 'CDI', 'Stage', 'Alternance'] as $type): ?>
                            <option value="<?= esc($type) ?>" <?= $ancien['type_contrat'] === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Salaire mensuel</label>
                    <input type="number" step="0.01" name="salaire" class="form-control" value="<?= esc($ancien['salaire_mensuel']) ?>" required>
                    <small class="help-block">Fourchette : <?= tf_money($poste['salaire_base']) ?> – <?= tf_money($poste['salaire_max']) ?></small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="form-group"><label>Date de début</label>
                    <input type="date" name="date_debut" class="form-control" value="<?= esc(date('Y-m-d', strtotime('+1 day', strtotime($ancien['date_debut'])))) ?>" required></div>
            </div>
            <div class="col-sm-4">
                <div class="form-group"><label>Date de fin</label>
                    <input type="date" name="date_fin" class="form-control" value="<?= esc($ancien['date_fin']) ?>"></div>
            </div>
            <div class="col-sm-4">
                <div class="form-group"><label>Période d’essai (mois)</label>
                    <input type="number" name="periode_essai" class="form-control" min="0" max="12" value="<?= esc($ancien['periode_essai_mois']) ?>"></div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="form-group"><label>Heure de début</label>
                    <input type="time" name="heure_debut" class="form-control" value="<?= esc(substr($ancien['heure_debut'], 0, 5)) ?>"></div>
            </div>
            <div class="col-sm-4">
                <div class="form-group"><label>Heure de fin</label>
                    <input type="time" name="heure_fin" class="form-control" value="<?= esc(substr($ancien['heure_fin'], 0, 5)) ?>"></div>
            </div>
            <div class="col-sm-4">
                <div class="form-group"><label>Poste</label>
                    <select name="poste_id" class="form-control">
                        <?php foreach ($postes as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $salarie['poste_id'] ? 'selected' : '' ?>><?= esc($p['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group"><label>Département</label>
                    <select name="departement_id" class="form-control">
                        <?php foreach ($departements as $d): ?>
                            <option value="<?= (int) $d['id'] ?>" <?= (int) $d['id'] === (int) $salarie['departement_id'] ? 'selected' : '' ?>><?= esc($d['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group"><label>Régime de travail</label>
                    <select name="regime_travail_id" class="form-control">
                        <?php foreach ($regimes as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= (int) $r['id'] === (int) $ancien['regime_travail_id'] ? 'selected' : '' ?>><?= esc($r['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-actions text-right">
            <a href="<?= base_url('contrats/salarie/' . $salarie['id']) ?>" class="btn btn-default">Annuler</a>
            <button class="btn btn-warning"><i class="fa fa-refresh"></i> Renouveler</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>