<?php
$c  = $candidature;
$ct = $contrat ?? null;
$old = function ($key, $default = '') use ($ct) {
    return old($key, $ct[$key] ?? $default);
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Proposer un contrat</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <style>
        .control-label { font-weight:600; color:#555; margin-bottom:6px; }
        .info-box { background:#fff; border-radius:8px; padding:20px;
                    box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:20px; }
        .info-box h4 { margin-top:0; color:#444; }
        .info-box h4 i { color:#f0ad4e; margin-right:8px; }
        .budget-warn { color:#b8791a; background:#fdf0d5; padding:8px 12px;
                       border-radius:4px; font-size:12px; }
    </style>
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'contrats']) ?>

<div class="container">
    <div class="col-md-10 col-md-offset-1">
        <h1 class="text-center"><?= $ct ? 'Modifier la proposition' : 'Proposer un contrat' ?></h1>
        <p class="text-center" style="color:#888;">Le candidat recevra cette proposition et devra l'accepter.</p>

        <?php if (session()->getFlashdata('error')) { ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php } ?>

        <div class="info-box">
            <h4><i class="fa fa-user"></i> Candidat</h4>
            <p><strong>Nom :</strong> <?= esc($c['nom']) ?> &nbsp;|&nbsp;
               <strong>Email :</strong> <?= esc($c['email']) ?></p>
            <p><strong>Poste :</strong> <?= esc($c['poste']) ?> &nbsp;|&nbsp;
               <strong>Salaire de base :</strong> <?= number_format($c['salaire_base'], 2) ?>
               &nbsp;|&nbsp;
               <strong>Salaire maximum autorisé :</strong> <?= number_format($c['salaire_max'], 2) ?></p>
            <div class="budget-warn">
                <i class="fa fa-info-circle"></i>
                Le salaire proposé doit rester inférieur ou égal au plafond du poste.
            </div>
        </div>

        <form class="form-horizontal templatemo-container"
              action="<?= base_url('contrats/save/' . $c['id']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="control-label">Type de contrat *</label>
                    <select name="type_contrat" id="type_contrat" class="form-control" required
                            onchange="toggleDateFin()">
                        <option value="">— Choisir —</option>
                        <option value="Essai" <?= $old('type_contrat') === 'Essai' ? 'selected' : '' ?>>Contrat d'essai</option>
                        <option value="CDD"   <?= $old('type_contrat') === 'CDD'   ? 'selected' : '' ?>>CDD (durée déterminée)</option>
                        <option value="CDI"   <?= $old('type_contrat') === 'CDI'   ? 'selected' : '' ?>>CDI (durée indéterminée)</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label class="control-label">Salaire proposé * (max <?= number_format($c['salaire_max'], 2) ?>)</label>
                    <input type="number" step="0.01" name="salaire_negocie" class="form-control" required
                           min="<?= esc($c['salaire_base']) ?>" max="<?= esc($c['salaire_max']) ?>"
                           value="<?= esc($old('salaire_negocie', $c['salaire_base'])) ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="control-label">Date de début *</label>
                    <input type="date" name="date_debut" class="form-control" required
                           value="<?= esc($old('date_debut', date('Y-m-d'))) ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label class="control-label">Date de fin <span id="date-fin-req" class="text-danger">*</span></label>
                    <input type="date" name="date_fin" id="date_fin" class="form-control"
                           value="<?= esc($old('date_fin')) ?>">
                    <small class="text-muted">Obligatoire pour Essai et CDD. Vide pour CDI.</small>
                </div>
            </div>

            <hr>
            <h4><i class="fa fa-clock-o"></i> Horaires de travail</h4>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="control-label">Heure de début *</label>
                    <input type="time" name="heure_debut" class="form-control" required
                           value="<?= esc($old('heure_debut', '08:00')) ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label class="control-label">Heure de fin *</label>
                    <input type="time" name="heure_fin" class="form-control" required
                           value="<?= esc($old('heure_fin', '17:00')) ?>">
                </div>
            </div>

            <hr>

            <div class="form-group text-right">
                <a href="<?= base_url('contrats') ?>" class="btn btn-default">
                    <i class="fa fa-times"></i> Annuler
                </a>
                <button type="submit" class="btn btn-warning">
                    <i class="fa fa-paper-plane"></i> Envoyer la proposition
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDateFin() {
    var type = document.getElementById('type_contrat').value;
    var df   = document.getElementById('date_fin');
    var req  = document.getElementById('date-fin-req');
    if (type === 'CDI' || type === '') {
        df.required = false;
        df.value = '';
        req.style.display = 'none';
    } else {
        df.required = true;
        req.style.display = 'inline';
    }
}
document.addEventListener('DOMContentLoaded', toggleDateFin);
</script>
</body>
</html>