<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes contrats</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <style>
        .contrat-card { background:#fff; border-radius:8px; padding:25px;
                        box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:20px;
                        border-top:4px solid #f0ad4e; }
        .contrat-card.accepte { border-top-color:#5cb85c; }
        .contrat-card.refuse  { border-top-color:#d9534f; }
        .contrat-card h3 { margin-top:0; color:#333; font-size:18px; }
        .contrat-info { display:flex; flex-wrap:wrap; gap:20px; margin:15px 0; }
        .contrat-info div { flex:1; min-width:150px; }
        .contrat-info label { display:block; font-size:11px; text-transform:uppercase;
                              color:#999; font-weight:600; margin-bottom:4px; letter-spacing:0.5px; }
        .contrat-info strong { color:#333; font-size:15px; }
        .question-box { background:#fdf0d5; border-left:4px solid #f0ad4e;
                        padding:12px 15px; margin:15px 0; border-radius:4px; font-size:13px; }
    </style>
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'mes_contrats']) ?>

<div class="container">
    <h1 class="text-center">Mes contrats</h1>
    <p class="text-center" style="color:#888;margin-bottom:30px;">
        Consultez la proposition, vérifiez les horaires et le salaire, puis acceptez ou refusez.
    </p>

    <?php if (session()->getFlashdata('error')) { ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php } ?>
    <?php if (session()->getFlashdata('success')) { ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php } ?>

    <?php if (empty($contrats)): ?>
        <div class="well text-center">Aucune proposition de contrat pour le moment.</div>
    <?php endif; ?>

    <?php foreach ($contrats as $c): ?>
        <?php
        $class = '';
        if ($c['statut'] === 'Accepté') $class = 'accepte';
        if ($c['statut'] === 'Refusé')  $class = 'refuse';
        ?>
        <div class="contrat-card <?= $class ?>">
            <h3><i class="fa fa-briefcase"></i> <?= esc($c['poste']) ?></h3>

            <div class="contrat-info">
                <div>
                    <label>Type de contrat</label>
                    <strong><?= esc(['Essai' => "Contrat d'essai", 'CDD' => 'CDD', 'CDI' => 'CDI'][$c['type_contrat']] ?? $c['type_contrat']) ?></strong>
                </div>
                <div>
                    <label>Date de début</label>
                    <strong><?= date('d/m/Y', strtotime($c['date_debut'])) ?></strong>
                </div>
                <div>
                    <label>Date de fin</label>
                    <strong><?= $c['date_fin'] ? date('d/m/Y', strtotime($c['date_fin'])) : 'Indéterminée' ?></strong>
                </div>
                <div>
                    <label>Salaire proposé</label>
                    <strong><?= number_format($c['salaire_negocie'], 2) ?></strong>
                </div>
                <div>
                    <label>Horaires</label>
                    <strong><?= substr($c['heure_debut'], 0, 5) ?> — <?= substr($c['heure_fin'], 0, 5) ?></strong>
                </div>
            </div>

            <?php if ($c['statut'] === 'Proposé'): ?>
                <div class="question-box">
                    <i class="fa fa-question-circle"></i>
                    <strong>Ces conditions vous conviennent-elles ?</strong><br>
                    Vérifiez bien les horaires de travail et le salaire proposé avant de répondre.
                </div>
                <form action="<?= base_url('contrats/accepter/' . $c['id']) ?>" method="post" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success"
                            onclick="return confirm('Accepter définitivement ce contrat ?');">
                        <i class="fa fa-check"></i> J'accepte ce contrat
                    </button>
                </form>
                <form action="<?= base_url('contrats/refuser/' . $c['id']) ?>" method="post" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Refuser cette proposition ?');">
                        <i class="fa fa-times"></i> Je refuse
                    </button>
                </form>

            <?php elseif ($c['statut'] === 'Accepté'): ?>
                <div class="alert alert-success" style="margin-bottom:15px;">
                    <i class="fa fa-check-circle"></i>
                    <strong>Contrat accepté le <?= date('d/m/Y à H:i', strtotime($c['date_reponse'])) ?></strong><br>
                    Vous pouvez télécharger votre contrat.
                </div>
                <a href="<?= base_url('contrats/pdf/' . $c['id']) ?>" class="btn btn-danger">
                    <i class="fa fa-file-pdf-o"></i> Télécharger mon contrat (PDF)
                </a>

            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fa fa-times-circle"></i>
                    <strong>Vous avez refusé cette proposition le <?= date('d/m/Y à H:i', strtotime($c['date_reponse'])) ?>.</strong>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>