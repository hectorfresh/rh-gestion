<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrats</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'contrats']) ?>

<div class="container">
    <h1 class="text-center">Génération de contrats</h1>
    <p class="text-center" style="color:#888;margin-bottom:30px;">
        Sélectionnez un candidat embauché pour générer son contrat.
    </p>

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
                <th>Salaire de base</th>
                <th>Salaire max</th>
                <th>Salaire négocié</th>
                <th>Contrat</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($candidatures as $c) { ?>
                <tr>
                    <td><?= esc($c['nom']) ?><br><small class="text-muted"><?= esc($c['email']) ?></small></td>
                    <td><?= esc($c['poste']) ?></td>
                    <td><?= number_format($c['salaire_base'], 2) ?></td>
                    <td><?= number_format($c['salaire_max'], 2) ?></td>
                    <td>
                        <?php if ($c['salaire_negocie']): ?>
                            <span class="label label-success"><?= number_format($c['salaire_negocie'], 2) ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($c['contrat_id']): ?>
                            <span class="label label-info"><i class="fa fa-check"></i> Généré</span>
                        <?php else: ?>
                            <span class="label label-warning">À générer</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= base_url('contrats/generer/' . $c['id']) ?>" class="btn btn-warning btn-xs">
                            <i class="fa fa-<?= $c['contrat_id'] ? 'edit' : 'plus' ?>"></i>
                            <?= $c['contrat_id'] ? 'Modifier' : 'Générer' ?>
                        </a>
                        <?php if ($c['contrat_id']): ?>
                            <a href="<?= base_url('contrats/pdf/' . $c['contrat_id']) ?>" class="btn btn-danger btn-xs">
                                <i class="fa fa-file-pdf-o"></i> PDF
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php } ?>

            <?php if (empty($candidatures)) { ?>
                <tr><td colspan="7" class="text-center">Aucun candidat embauché.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>