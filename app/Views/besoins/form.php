<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-plus-circle text-warning"></i> Nouveau besoin</h1>
        <p class="page-subtitle">
            Le besoin est soumis au circuit RH → finance (automatique) → DG.
            <?php if ($role === 'manager'): ?>
                Vous ne pouvez créer que pour votre département.
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= base_url('besoins') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<div class="app-card">
    <form method="post" action="<?= base_url('besoins') ?>" class="form-horizontal" id="besoin-form">
        <?= csrf_field() ?>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Département <span class="text-danger">*</span></label>
                    <select name="departement_id" id="departement_id" class="form-control" required>
                        <option value="">— Choisir un département —</option>
                        <?php foreach ($departements as $departement): ?>
                            <option value="<?= (int) $departement['id'] ?>"
                                <?= (string) old('departement_id', $departement_id) === (string) $departement['id'] ? 'selected' : '' ?>>
                                <?= esc($departement['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Poste <span class="text-danger">*</span></label>
                    <select name="poste_id" id="poste_id" class="form-control" required>
                        <option value="">— Choisir un poste —</option>
                        <?php foreach ($postes as $poste): ?>
                            <option value="<?= (int) $poste['id'] ?>"
                                    data-departement="<?= (int) $poste['departement_id'] ?>"
                                    data-base="<?= (float) $poste['salaire_base'] ?>"
                                    data-max="<?= (float) $poste['salaire_max'] ?>"
                                    <?= (string) old('poste_id') === (string) $poste['id'] ? 'selected' : '' ?>>
                                <?= esc($poste['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="help-block">Les postes sont filtrés selon le département.</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Nombre de postes <span class="text-danger">*</span></label>
                    <input type="number" name="nombre" class="form-control" min="1"
                           value="<?= esc(old('nombre', '1')) ?>" required>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Type de besoin</label>
                    <select name="type_besoin" class="form-control">
                        <option value="creation" <?= old('type_besoin') === 'creation' ? 'selected' : '' ?>>Création de poste</option>
                        <option value="remplacement" <?= old('type_besoin') === 'remplacement' ? 'selected' : '' ?>>Remplacement</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Date souhaitée</label>
                    <input type="date" name="date_souhaitee" class="form-control"
                           min="<?= date('Y-m-d') ?>" value="<?= esc(old('date_souhaitee')) ?>">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Salaire mensuel estimé <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="salaire_estime" id="salaire_estime"
                           class="form-control" value="<?= esc(old('salaire_estime')) ?>" required>
                    <small class="help-block" id="fourchette">
                        Choisissez un poste : le salaire de base est proposé et reste modifiable dans la fourchette.
                    </small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Motif</label>
                    <input type="text" name="motif" class="form-control" maxlength="255" value="<?= esc(old('motif')) ?>"
                           placeholder="Ex. : croissance de l’équipe, départ d’un collaborateur…">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Description du besoin</label>
            <textarea name="description" class="form-control" rows="4"
                      placeholder="Missions, compétences attendues…"><?= esc(old('description')) ?></textarea>
        </div>

        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            À l’enregistrement, l’étape <strong>finance</strong> est calculée automatiquement
            (budget annuel, masse salariale, engagements, coût et reste disponible) et conservée
            dans l’historique du besoin.
        </div>

        <div class="form-actions text-right">
            <a href="<?= base_url('besoins') ?>" class="btn btn-default">Annuler</a>
            <button type="submit" class="btn btn-warning"><i class="fa fa-save"></i> Soumettre le besoin</button>
        </div>
    </form>
</div>

<script>
(function () {
    var departement = document.getElementById('departement_id');
    var poste = document.getElementById('poste_id');
    var salaire = document.getElementById('salaire_estime');
    var aide = document.getElementById('fourchette');

    function filtrerPostes() {
        var dept = departement.value;
        var premier = null;
        Array.prototype.forEach.call(poste.options, function (option) {
            if (!option.value) { return; }
            var visible = !dept || option.dataset.departement === dept;
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible && premier === null) { premier = option; }
        });

        var courant = poste.options[poste.selectedIndex];
        if (!courant || !courant.value || courant.disabled) {
            poste.value = '';
            if (premier) { poste.value = premier.value; }
        }
        appliquerPoste();
    }

    function appliquerPoste(forcer) {
        var option = poste.options[poste.selectedIndex];
        if (!option || !option.value) {
            salaire.removeAttribute('min');
            salaire.removeAttribute('max');
            return;
        }

        var base = Number(option.dataset.base);
        var max = Number(option.dataset.max);
        salaire.min = base;
        salaire.max = max;
        if (forcer || !salaire.value) {
            salaire.value = base;
        }
        aide.textContent = 'Fourchette du poste : ' + base.toLocaleString('fr-FR') +
            ' – ' + max.toLocaleString('fr-FR') + ' Ar. Le salaire de base est proposé par défaut.';
    }

    departement.addEventListener('change', filtrerPostes);
    poste.addEventListener('change', function () { appliquerPoste(true); });
    filtrerPostes();
})();
</script>
<?= $this->endSection() ?>
