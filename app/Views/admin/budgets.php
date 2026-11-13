<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-money text-warning"></i> Budgets par département</h1>
        <p class="page-subtitle">
            Budget annuel, masse salariale, engagements et disponible pour l’année <?= (int) $annee ?>.
        </p>
    </div>
    <div class="page-actions">
        <form method="get" action="<?= base_url('admin/budgets') ?>" class="form-inline">
            <label>Année</label>
            <input type="number" name="annee" class="form-control input-sm" value="<?= (int) $annee ?>" min="2000" max="2100" style="width:95px">
            <button class="btn btn-sm btn-default">Afficher</button>
        </form>
        <a href="<?= base_url('admin') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Administration</a>
    </div>
</div>

<div class="app-card">
    <h4 class="app-card-title">Tableau budgétaire <?= (int) $annee ?></h4>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Département</th>
                    <th class="text-right">Budget annuel</th>
                    <th class="text-right">Masse salariale</th>
                    <th class="text-right">Engagé</th>
                    <th class="text-right">Disponible</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lignes as $ligne): ?>
                <tr class="<?= $ligne['alerte'] ? 'danger' : '' ?>">
                    <td><strong><?= esc($ligne['libelle']) ?></strong> <small class="text-muted"><?= esc($ligne['code']) ?></small></td>
                    <td class="text-right money"><?= tf_money($ligne['budget']) ?></td>
                    <td class="text-right money"><?= tf_money($ligne['masse']) ?></td>
                    <td class="text-right money"><?= tf_money($ligne['engagements']) ?></td>
                    <td class="text-right money <?= $ligne['alerte'] ? 'text-danger' : 'text-success' ?>">
                        <strong><?= tf_money($ligne['disponible']) ?></strong>
                    </td>
                    <td class="text-right">
                        <?php if ($ligne['alerte']): ?>
                            <span class="status-badge status-red">Dépassement</span>
                        <?php else: ?>
                            <span class="status-badge status-green">OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted small">
        Masse salariale = contrats actifs du département × 12.
        Engagé = besoins <strong>validés</strong> non pourvus, valorisés au salaire estimé (ou au salaire
        de la proposition en cours), sur l’année sélectionnée.
    </p>
</div>

<div class="app-card">
    <h4 class="app-card-title"><i class="fa fa-pencil"></i> Saisir / modifier un budget</h4>
    <form method="post" action="<?= base_url('admin/budgets') ?>" class="form-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="annee" value="<?= (int) $annee ?>">
        <select name="departement_id" class="form-control input-sm" required>
            <option value="">— Département —</option>
            <?php foreach ($departements as $d): ?>
                <option value="<?= (int) $d['id'] ?>"><?= esc($d['libelle']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" step="0.01" min="0" name="montant_annuel" class="form-control input-sm"
               placeholder="Montant annuel" required style="min-width:190px">
        <button class="btn btn-sm btn-warning"><i class="fa fa-save"></i> Enregistrer</button>
    </form>
    <p class="text-muted small" style="margin-top:10px">
        La saisie est réservée aux rôles RH et DG. Toute modification est journalisée dans l’audit.
    </p>
</div>
<?= $this->endSection() ?>
