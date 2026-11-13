<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-cogs text-warning"></i> Administration</h1>
        <p class="page-subtitle">Référentiels RH, budgets et jours fériés.</p></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="app-card">
            <h4 class="app-card-title">Départements</h4>
            <table class="table table-condensed">
                <thead><tr><th>Code</th><th>Libellé</th><th>Description</th></tr></thead>
                <tbody>
                <?php foreach ($departements as $d): ?>
                    <tr><td><strong><?= esc($d['code']) ?></strong></td><td><?= esc($d['libelle']) ?></td><td><small><?= esc($d['description']) ?></small></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <form method="post" action="<?= base_url('admin/departements') ?>" class="form-inline">
                <?= csrf_field() ?>
                <input type="text" name="code" class="form-control input-sm" placeholder="Code" required style="width:90px">
                <input type="text" name="libelle" class="form-control input-sm" placeholder="Libellé" required>
                <input type="text" name="description" class="form-control input-sm" placeholder="Description">
                <button class="btn btn-sm btn-warning">Ajouter</button>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="app-card">
            <h4 class="app-card-title">Postes</h4>
            <div class="table-responsive">
                <table class="table table-condensed">
                    <thead><tr><th>Poste</th><th>Département</th><th class="text-right">Base</th><th class="text-right">Max</th></tr></thead>
                    <tbody>
                    <?php foreach ($postes as $p): ?>
                        <tr><td><?= esc($p['libelle']) ?></td><td><?= esc($p['departement']) ?></td>
                            <td class="text-right money"><?= tf_money($p['salaire_base']) ?></td>
                            <td class="text-right money"><?= tf_money($p['salaire_max']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <form method="post" action="<?= base_url('admin/postes') ?>">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-sm-6"><input type="text" name="libelle" class="form-control input-sm" placeholder="Libellé du poste" required></div>
                    <div class="col-sm-6"><select name="departement_id" class="form-control input-sm" required>
                        <?php foreach ($departements as $d): ?><option value="<?= (int) $d['id'] ?>"><?= esc($d['libelle']) ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="col-sm-5" style="margin-top:6px"><input type="number" name="salaire_base" class="form-control input-sm" placeholder="Salaire base" required></div>
                    <div class="col-sm-5" style="margin-top:6px"><input type="number" name="salaire_max" class="form-control input-sm" placeholder="Salaire max" required></div>
                    <div class="col-sm-2" style="margin-top:6px"><button class="btn btn-sm btn-warning btn-block">+</button></div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="app-card">
            <h4 class="app-card-title">Budgets <?= (int) $annee ?></h4>
            <table class="table table-condensed">
                <thead><tr><th>Département</th><th class="text-right">Montant annuel</th></tr></thead>
                <tbody>
                <?php foreach ($budgets as $b): ?>
                    <tr><td><?= esc($b['departement']) ?></td><td class="text-right money"><?= tf_money($b['montant_annuel']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <a href="<?= base_url('admin/budgets?annee=' . (int) $annee) ?>" class="btn btn-warning btn-block">
                <i class="fa fa-table"></i> Tableau budget / masse / engagé / disponible
            </a>
        </div>
    </div>

    <div class="col-md-6">
        <div class="app-card">
            <h4 class="app-card-title">Types de congé</h4>
            <table class="table table-condensed">
                <thead><tr><th>Type</th><th class="text-right">Quota</th><th>Justificatif</th></tr></thead>
                <tbody>
                <?php foreach ($types_conge as $t): ?>
                    <tr><td><?= esc($t['libelle']) ?></td>
                        <td class="text-right"><?= $t['jours_max_par_an'] !== null ? (float) $t['jours_max_par_an'] . ' j' : '—' ?></td>
                        <td><?= (int) $t['justificatif_requis'] === 1 ? 'Oui' : 'Non' ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <form method="post" action="<?= base_url('admin/types-conge') ?>" class="form-inline">
                <?= csrf_field() ?>
                <input type="text" name="libelle" class="form-control input-sm" placeholder="Libellé" required>
                <input type="number" step="0.5" name="jours_max_par_an" class="form-control input-sm" placeholder="Quota" style="width:80px">
                <label class="checkbox-inline"><input type="checkbox" name="justificatif_requis" value="1"> Justif.</label>
                <button class="btn btn-sm btn-warning">Ajouter</button>
            </form>
        </div>
    </div>
</div>

<div class="app-card">
    <h4 class="app-card-title">Jours fériés <?= (int) $annee ?></h4>
    <div class="row">
        <div class="col-md-7">
            <table class="table table-condensed">
                <thead><tr><th>Date</th><th>Libellé</th></tr></thead>
                <tbody>
                <?php foreach ($jours_feries as $j): ?>
                    <tr><td><?= tf_date($j['jour']) ?></td><td><?= esc($j['libelle']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-5">
            <form method="post" action="<?= base_url('admin/jours-feries') ?>">
                <?= csrf_field() ?>
                <div class="form-group"><label>Date</label><input type="date" name="jour" class="form-control" required></div>
                <div class="form-group"><label>Libellé</label><input type="text" name="libelle" class="form-control" required></div>
                <button class="btn btn-warning">Ajouter un jour férié</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
