<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générer un entretien</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'entretiens']) ?>

<div class="container">
    <h1 class="text-center">Générer un entretien</h1>

    <?php if (session()->getFlashdata('error')) { ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php } ?>
    <?php if (session()->getFlashdata('success')) { ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php } ?>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Candidat</th>
                <th>Poste</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($candidatures as $c) { ?>
                <tr>
                    <td><?= esc($c['nom']) ?></td>
                    <td><?= esc($c['poste']) ?></td>
                    <td>
                        <a href="<?= base_url('entretiens/demander/' . $c['id']) ?>" class="btn btn-warning btn-xs">
                            <i class="fa fa-calendar"></i> Demander un entretien
                        </a>
                    </td>
                </tr>
            <?php } ?>

            <?php if (empty($candidatures)) { ?>
                <tr><td colspan="3" class="text-center">Aucune candidature acceptée.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>