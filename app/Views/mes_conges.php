<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes congés</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <style>
        .solde-card { background:#fff; border-radius:8px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.08); border-top:4px solid #3498db; text-align:center; }
        .solde-card h4 { margin:0 0 10px; color:#333; font-weight:600; }
        .solde-card .chiffre { font-size:32px; font-weight:700; color:#f0ad4e; }
        .solde-card .unite { font-size:14px; color:#888; }
        .solde-card .details { font-size:12px; color:#666; margin-top:8px; }
    </style>
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'mes_conges']) ?>
<div class="container">
    <h1 class="text-center">Mes congés (<?= $annee ?>)</h1>
    
    <?php if (session()->getFlashdata('error')) { ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php } ?>
    <?php if (session()->getFlashdata('success')) { ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php } ?>

    <div class="row">
        <?php foreach($soldes as $s): ?>
            <div class="col-md-4">
                <div class="solde-card" style="border-top-color: <?= $s['couleur'] ?>;">
                    <h4><?= esc($s['libelle']) ?></h4>
                    <div class="chiffre"><?= $s['jours_acquis'] - $s['jours_pris'] ?></div>
                    <div class="unite">jours restants</div>
                    <div class="details">(Acquis: <?= $s['jours_acquis'] ?> | Pris: <?= $s['jours_pris'] ?>)</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center" style="margin: 30px 0;">
        <a href="<?= base_url('conges/formulaire') ?>" class="btn btn-warning btn-lg">
            <i class="fa fa-plus-circle"></i> Nouvelle demande
        </a>
    </div>

    <h3>Historique de mes demandes</h3>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Type</th>
                <th>Du</th>
                <th>Au</th>
                <th>Jours</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($demandes as $d): ?>
                <tr>
                    <td><span class="label" style="background:<?= $d['couleur'] ?>"><?= esc($d['libelle']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($d['date_debut'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($d['date_fin'])) ?></td>
                    <td><?= $d['nombre_jours'] ?></td>
                    <td>
                        <?php 
                            $labelClass = 'default';
                            if ($d['statut_libelle'] == 'Accepté') $labelClass = 'success';
                            if ($d['statut_libelle'] == 'Refusé') $labelClass = 'danger';
                            if ($d['statut_libelle'] == 'Annulé') $labelClass = 'warning';
                        ?>
                        <span class="label label-<?= $labelClass ?>"><?= esc($d['statut_libelle']) ?></span>
                    </td>
                    <td>
                        <?php if ($d['statut_libelle'] == 'En attente'): ?>
                            <a href="<?= base_url('conges/annuler/' . $d['id']) ?>" class="btn btn-danger btn-xs" onclick="return confirm('Annuler cette demande ?');">
                                <i class="fa fa-times"></i> Annuler
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>