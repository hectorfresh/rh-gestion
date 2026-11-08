<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation des congés</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'validation_conges']) ?>
<div class="container">
    <h1 class="text-center">Validation des congés</h1>

    <?php if (session()->getFlashdata('error')) { ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php } ?>
    <?php if (session()->getFlashdata('success')) { ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php } ?>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Employé</th>
                <th>Type</th>
                <th>Du</th>
                <th>Au</th>
                <th>Jours</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($demandes as $d): ?>
                <tr>
                    <td><?= esc($d['nom']) ?></td>
                    <td><?= esc($d['type_libelle']) ?></td>
                    <td><?= date('d/m/Y', strtotime($d['date_debut'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($d['date_fin'])) ?></td>
                    <td><?= $d['nombre_jours'] ?></td>
                    <td>
                        <form action="<?= base_url('conges/accepter/' . $d['id']) ?>" method="post" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-success btn-xs" onclick="return confirm('Accepter ce congé ?');">
                                <i class="fa fa-check"></i> Accepter
                            </button>
                        </form>
                        <form action="<?= base_url('conges/refuser/' . $d['id']) ?>" method="post" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Refuser ce congé ?');">
                                <i class="fa fa-times"></i> Refuser
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($demandes)): ?>
                <tr><td colspan="6" class="text-center">Aucune demande en attente.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>