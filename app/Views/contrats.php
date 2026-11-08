<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrats</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'contrats']) ?>

<div class="container">
    <h1 class="text-center">Propositions de contrats</h1>
    <p class="text-center" style="color:#888;margin-bottom:30px;">
        Proposez un contrat aux candidats embauchés, puis attendez leur validation.
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
                <th>Salaire max</th>
                <th>Salaire proposé</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($candidatures as $c) { ?>
                <tr>
                    <td><?= esc($c['nom']) ?><br><small class="text-muted"><?= esc($c['email']) ?></small></td>
                    <td><?= esc($c['poste']) ?></td>
                    <td><?= number_format($c['salaire_max'], 2) ?></td>
                    <td>
                        <?php if ($c['salaire_negocie']): ?>
                            <strong><?= number_format($c['salaire_negocie'], 2) ?></strong>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$c['contrat_id']): ?>
                            <span class="label label-default">Aucune proposition</span>
                        <?php elseif ($c['contrat_statut'] === 'Proposé'): ?>
                            <span class="label label-warning">
                                <i class="fa fa-clock-o"></i> En attente du candidat
                            </span>
                        <?php elseif ($c['contrat_statut'] === 'Accepté'): ?>
                            <span class="label label-success">
                                <i class="fa fa-check"></i> Accepté
                            </span>
                        <?php elseif ($c['contrat_statut'] === 'Refusé'): ?>
                            <span class="label label-danger">
                                <i class="fa fa-times"></i> Refusé
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$c['contrat_id']): ?>
                            <a href="<?= base_url('contrats/generer/' . $c['id']) ?>" class="btn btn-warning btn-xs">
                                <i class="fa fa-plus"></i> Proposer un contrat
                            </a>
                        <?php else: ?>
                            <?php if ($c['contrat_statut'] === 'Proposé'): ?>
                                <a href="<?= base_url('contrats/generer/' . $c['id']) ?>" class="btn btn-default btn-xs">
                                    <i class="fa fa-edit"></i> Modifier
                                </a>
                            <?php elseif ($c['contrat_statut'] === 'Refusé'): ?>
                                <a href="<?= base_url('contrats/generer/' . $c['id']) ?>" class="btn btn-warning btn-xs">
                                    <i class="fa fa-refresh"></i> Re-proposer
                                </a>
                            <?php endif; ?>

                            <?php if ($c['contrat_statut'] === 'Accepté'): ?>
                                <a href="<?= base_url('contrats/pdf/' . $c['contrat_id']) ?>" class="btn btn-danger btn-xs">
                                    <i class="fa fa-file-pdf-o"></i> PDF
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php } ?>

            <?php if (empty($candidatures)) { ?>
                <tr><td colspan="6" class="text-center">Aucun candidat embauché.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>