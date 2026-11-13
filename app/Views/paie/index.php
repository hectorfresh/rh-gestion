<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-money text-warning"></i> Paie variable</h1>
        <p class="page-subtitle">Salaire fixe du contrat + primes, heures supplémentaires et retenues.</p></div>
    <a href="<?= base_url('paie/export?mois=' . urlencode($mois)) ?>" class="btn btn-default"><i class="fa fa-download"></i> Export CSV</a>
</div>

<div class="app-card">
    <form method="get" class="form-inline">
        <label>Mois :</label>
        <input type="month" name="mois" class="form-control input-sm" value="<?= esc($mois) ?>">
        <label>Département :</label>
        <select name="departement_id" class="form-control input-sm">
            <option value="">Tous</option>
            <?php foreach ($departements as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= $departement_id === (int) $d['id'] ? 'selected' : '' ?>><?= esc($d['libelle']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-warning">Afficher</button>
    </form>
</div>

<div class="row">
    <div class="col-md-3"><div class="app-stat"><div class="stat-label">Salariés</div><div class="stat-value"><?= (int) $totaux['salaries'] ?></div></div></div>
    <div class="col-md-3"><div class="app-stat green"><div class="stat-label">Total brut</div><div class="stat-value" style="font-size:18px"><?= tf_money($totaux['brut']) ?></div></div></div>
    <div class="col-md-3"><div class="app-stat blue"><div class="stat-label">Total net</div><div class="stat-value" style="font-size:18px"><?= tf_money($totaux['net']) ?></div></div></div>
</div>

<div class="app-card">
    <h4 class="app-card-title">Synthèse <?= esc($mois) ?></h4>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Matricule</th><th>Salarié</th><th>Département</th><th>Fixe</th><th>Gains var.</th><th>Retenues</th><th>Brut</th><th>Net</th></tr></thead>
            <tbody>
            <?php foreach ($lignes as $l): ?>
                <tr>
                    <td><?= esc($l['matricule']) ?></td>
                    <td><?= esc($l['prenom'] . ' ' . $l['nom']) ?><br><small class="text-muted"><?= esc($l['poste']) ?></small></td>
                    <td><?= esc($l['departement']) ?></td>
                    <td class="money"><?= tf_money($l['salaire_fixe']) ?></td>
                    <td class="money text-success"><?= tf_money($l['gains_variables']) ?></td>
                    <td class="money text-danger"><?= tf_money($l['retenues']) ?></td>
                    <td class="money"><?= tf_money($l['brut']) ?></td>
                    <td class="money"><strong><?= tf_money($l['net']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="app-card">
            <h4 class="app-card-title">Ajouter un élément variable</h4>
            <form method="post" action="<?= base_url('paie/variables') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="mois" value="<?= esc($mois) ?>">
                <div class="form-group"><label>Salarié</label>
                    <select name="salarie_id" class="form-control" required>
                        <?php foreach ($salaries as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= esc($s['prenom'] . ' ' . $s['nom']) ?> (<?= esc($s['matricule']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Libellé</label><input type="text" name="libelle" class="form-control" required></div>
                <div class="row">
                    <div class="col-sm-6"><div class="form-group"><label>Montant</label>
                        <input type="number" step="0.01" min="0" name="montant" class="form-control" required></div></div>
                    <div class="col-sm-6"><div class="form-group"><label>Type</label>
                        <select name="type" class="form-control">
                            <option value="prime">Prime</option>
                            <option value="heure_sup">Heures sup.</option>
                            <option value="retenue">Retenue</option>
                            <option value="autre">Autre</option>
                        </select></div></div>
                </div>
                <button class="btn btn-warning btn-block">Ajouter</button>
            </form>
        </div>
    </div>
    <div class="col-md-7">
        <div class="app-card">
            <h4 class="app-card-title">Éléments du mois</h4>
            <?php if (empty($variables)): ?>
                <div class="empty-state"><i class="fa fa-money"></i>Aucun élément variable.</div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead><tr><th>Salarié</th><th>Libellé</th><th>Type</th><th>Montant</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($variables as $v): ?>
                        <tr>
                            <td><?= esc($v['prenom'] . ' ' . $v['nom']) ?><br><small class="text-muted"><?= esc($v['matricule']) ?></small></td>
                            <td><?= esc($v['libelle']) ?></td>
                            <td><?= tf_badge($v['type']) ?></td>
                            <td class="money"><?= tf_money($v['montant']) ?></td>
                            <td class="text-right">
                                <form method="post" action="<?= base_url('paie/variables/' . $v['id'] . '/supprimer') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-danger" onclick="return confirm('Supprimer ?')"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
