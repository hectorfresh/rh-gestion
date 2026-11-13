<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-plane text-warning"></i> Nouvelle demande de congé</h1>
        <p class="page-subtitle">Le solde est contrôlé avant la validation manager puis RH.</p></div>
    <a href="<?= base_url('conges/mes') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="app-card">
            <form method="post" action="<?= base_url('conges') ?>">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Type de congé <span class="text-danger">*</span></label>
                    <select name="type_conge_id" id="type_conge_id" class="form-control" required>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= (int) $type['id'] ?>"
                                    data-justificatif="<?= (int) $type['justificatif_requis'] ?>">
                                <?= esc($type['libelle']) ?>
                                <?= $type['jours_max_par_an'] !== null ? '— quota ' . (float) $type['jours_max_par_an'] . ' j/an' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group"><label>Du <span class="text-danger">*</span></label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" required value="<?= esc(old('date_debut')) ?>"></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group"><label>Au <span class="text-danger">*</span></label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" required value="<?= esc(old('date_fin')) ?>"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="demi_journee" value="1"> Demi-journée (si début = fin)
                    </label>
                </div>
                <div class="form-group">
                    <label>Motif</label>
                    <textarea name="motif" class="form-control" rows="3" placeholder="Motif facultatif…"><?= esc(old('motif')) ?></textarea>
                </div>
                <div class="form-group" id="bloc-justificatif" style="display:none">
                    <label>Justificatif (référence ou description)</label>
                    <input type="text" name="justificatif" class="form-control" value="<?= esc(old('justificatif')) ?>">
                </div>
                <div class="form-actions text-right">
                    <a href="<?= base_url('conges/mes') ?>" class="btn btn-default">Annuler</a>
                    <button class="btn btn-warning"><i class="fa fa-send"></i> Envoyer la demande</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-5">
        <div class="app-card">
            <h4 class="app-card-title">Mes soldes <?= date('Y') ?></h4>
            <?php foreach ($soldes as $solde): ?>
                <p style="margin-bottom:10px">
                    <strong><?= esc($solde['libelle']) ?></strong>
                    <span class="pull-right status-badge status-green"><?= number_format((float) $solde['disponible'], 1, ',', ' ') ?> j</span>
                </p>
            <?php endforeach; ?>
            <p class="text-muted small">Les jours sont calculés automatiquement. Un congé ne peut pas chevaucher deux années civiles.</p>
        </div>
    </div>
</div>

<script>
(function () {
    var type = document.getElementById('type_conge_id');
    var bloc = document.getElementById('bloc-justificatif');
    function maj() {
        var option = type.options[type.selectedIndex];
        bloc.style.display = option && option.dataset.justificatif === '1' ? 'block' : 'none';
    }
    type.addEventListener('change', maj); maj();
})();
</script>
<?= $this->endSection() ?>
