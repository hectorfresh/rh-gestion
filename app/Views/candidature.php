<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des candidatures</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'candidatures']) ?>

<div class="container">
    <h1 class="text-center">Liste des candidatures</h1>

    <?php if (session()->getFlashdata('error')) { ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php } ?>
    <?php if (session()->getFlashdata('success')) { ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php } ?>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Poste</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($candidatures as $candidature) { ?>
                <tr>
                    
                    <td><?= esc($candidature['nom']) ?></td>
                    <td><?= esc($candidature['email']) ?></td>
                    <td><?= esc($candidature['poste']) ?></td>
                    <td><?= esc($candidature['statut']) ?></td>
                    <td>
                        <a href="<?= base_url('candidatures/' . $candidature['id']) ?>" class="btn btn-info btn-xs">
                            <i class="fa fa-eye"></i> Voir
                        </a>
                        <?php if ($candidature['statut'] === 'En attente') { ?>
                            <form action="<?= base_url('candidatures/valider/' . $candidature['id']) ?>"
                                  method="post" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-success btn-xs">
                                    <i class="fa fa-check"></i> Valider
                                </button>
                            </form>
                            <form action="<?= base_url('candidatures/refuser/' . $candidature['id']) ?>"
                                  method="post" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger btn-xs">
                                    <i class="fa fa-times"></i> Refuser
                                </button>
                            </form>
                        <?php } else { ?>
                            Aucune action disponible
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>

            <?php if (empty($candidatures)) { ?>
                <tr><td colspan="6" class="text-center">Aucune candidature.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>