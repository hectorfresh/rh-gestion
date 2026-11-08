<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvelle demande de congé</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'mes_conges']) ?>
<div class="container">
    <div class="col-md-6 col-md-offset-3">
        <h1 class="text-center">Demander un congé</h1>

        <?php if (session()->getFlashdata('error')) { ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php } ?>

        <form action="<?= base_url('conges/save') ?>" method="post" class="templatemo-container" style="padding:20px;">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>Type de congé *</label>
                <select name="type_conge_id" class="form-control" required>
                    <option value="">— Choisir —</option>
                    <?php foreach($types as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= esc($t['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Date de début *</label>
                    <input type="date" name="date_debut" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label>Date de fin *</label>
                    <input type="date" name="date_fin" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Motif / Commentaire</label>
                <textarea name="motif" class="form-control" rows="3" placeholder="Ex: Vacances en famille..."></textarea>
            </div>

            <div class="form-group text-right">
                <a href="<?= base_url('conges/mes') ?>" class="btn btn-default">Annuler</a>
                <button type="submit" class="btn btn-warning">Envoyer la demande</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>