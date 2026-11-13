<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-cogs text-warning"></i> Types et droits de congé</h1>
        <p class="page-subtitle">Gestion des types et des droits par ancienneté.</p></div>
    <a href="<?= base_url('admin') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Administration</a>
</div>

<div class="app-card">
    <h4 class="app-card-title">Types de congé</h4>
    <table class="table table-hover">
        <thead><tr><th>Libellé</th><th>Quota annuel</th><th>Justificatif</th><th>Droits</th></tr></thead>
        <tbody>
        <?php foreach ($types as $type): ?>
            <tr>
                <td><?= esc($type['libelle']) ?></td>
                <td><?= $type['jours_max_par_an'] !== null ? number_format((float) $type['jours_max_par_an'], 2, ',', ' ') . ' j' : 'Illimité' ?></td>
                <td><?= (int) $type['justificatif_requis'] === 1 ? 'Oui' : 'Non' ?></td>
                <td>
                    <?php foreach ($type['droits'] as $droit): ?>
                        <small><?= (int) $droit['anciennete_min_mois'] ?> mois min : <?= number_format((float) $droit['jours_par_mois'], 2, ',', ' ') ?> j/mois</small><br>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="app-card">
    <h4 class="app-card-title">Ajouter un droit de congé</h4>
    <form method="post" action="<?= base_url('admin/droits-conge') ?>" class="form-inline">
        <?= csrf_field() ?>
        <select name="type_conge_id" class="form-control input-sm" required>
            <?php foreach ($types as $type): ?>
                <option value="<?= (int) $type['id'] ?>"><?= esc($type['libelle']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="anciennete_min_mois" class="form-control input-sm" placeholder="Ancienneté min (mois)" min="0" required>
        <input type="number" step="0.01" name="jours_par_mois" class="form-control input-sm" placeholder="Jours/mois" min="0" required>
        <input type="number" step="0.01" name="jours_max_annuel" class="form-control input-sm" placeholder="Max annuel">
        <button class="btn btn-sm btn-warning">Enregistrer</button>
    </form>
</div>
<?= $this->endSection() ?>