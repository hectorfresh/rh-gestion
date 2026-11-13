<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-pencil text-warning"></i> Modifier la fiche salarié</h1>
        <p class="page-subtitle"><?= esc($salarie['prenom'] . ' ' . $salarie['nom']) ?> — <?= esc($salarie['matricule']) ?></p>
    </div>
    <a href="<?= base_url('salaries/' . $salarie['id']) ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<div class="app-card">
    <form method="post" action="<?= base_url('salaries/' . $salarie['id'] . '/modifier') ?>">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group"><label>Nom</label><input type="text" name="nom" class="form-control" value="<?= esc($salarie['nom']) ?>" required></div>
            </div>
            <div class="col-sm-6">
                <div class="form-group"><label>Prénom</label><input type="text" name="prenom" class="form-control" value="<?= esc($salarie['prenom']) ?>" required></div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= esc($salarie['email']) ?>" required></div>
            </div>
            <div class="col-sm-6">
                <div class="form-group"><label>Téléphone</label><input type="text" name="telephone" class="form-control" value="<?= esc($salarie['telephone']) ?>"></div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group"><label>Adresse</label><input type="text" name="adresse" class="form-control" value="<?= esc($salarie['adresse']) ?>"></div>
            </div>
            <div class="col-sm-6">
                <div class="form-group"><label>Ville</label><input type="text" name="ville" class="form-control" value="<?= esc($salarie['ville']) ?>"></div>
            </div>
        </div>
        <div class="form-actions text-right">
            <a href="<?= base_url('salaries/' . $salarie['id']) ?>" class="btn btn-default">Annuler</a>
            <button class="btn btn-warning"><i class="fa fa-save"></i> Enregistrer</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>