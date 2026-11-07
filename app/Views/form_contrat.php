<?php
$c = $candidature;
$ct = $contrat ?? null;
$old = function ($key, $default = '') use ($ct) {
    return old($key, $ct[$key] ?? $default);
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générer un contrat</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">
    <style>
        .templatemo-contact-form-2 .control-label {
            text-align: left !important; font-weight: 600; color: #555;
            margin-bottom: 6px; font-size: 13px; padding-top: 0;
        }
        .info-box { background:#fff; border-radius:8px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:20px; }
        .info-box h4 { margin-top:0; color:#444; }
        .info-box h4 i { color:#f0ad4e; margin-right:8px; }
        .budget-warn { color:#b8791a; background:#fdf0d5; padding:8px 12px; border-radius:4px; font-size:12px; }
    </style>
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'contrats']) ?>

<div class="container">
    <div class="col-md-10 col-md-offset-1">
        <h1 class="text-center"><?= $ct ? 'Modifier le contrat' : 'Générer le contrat' ?></h1>

        <?php if (session()->getFlashdata('error')) { ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php } ?>

        <div class="info-box">
            <h4><i class="fa fa-user"></i> Candidat</h4>
            <p><strong>Nom :</strong> <?= esc($c['nom']) ?> &nbsp; | &nbsp;
               <strong>Email :</strong> <?= esc($c['email']) ?></p>
            <p><strong>Poste :</strong> <?= esc($c['poste']) ?> &nbsp; | &nbsp;
               <strong>Salaire de base :</strong> <?= number_format($c['salaire_base'], 2) ?>
               &nbsp; | &nbsp;
               <strong>Salaire maximum :</strong> <?= number_format($c['salaire_max'], 2) ?></p>
            <div class="budget-warn">
                <i class="fa fa-info-circle"></i>
                Le salaire négocié doit être compris entre le salaire de base et le salaire maximum autorisé pour ce poste.
            </div>
        </div>

        <form class="form-horizontal templatemo-container" action="<?= base_url('contrats/save/' . $c['id']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-6">
<div class="form-group">
    <label class="control-label">Type de contrat *</label>
    <select name="type_contrat" id="type_contrat" class="form-control" required
            onchange="toggleDateFin()">
        <option value="">— Choisir —</option>
        <option value="Essai" <?= $old('type_contrat') === 'Essai' ? 'selected' : '' ?>>Contrat d'essai</option>
        <option value="CDD"   <?= $old('type_contrat') === 'CDD'   ? 'selected' : '' ?>>CDD (durée déterminée)</option>
        <option value="CDI"   <?= $old('type_contrat') === 'CDI'   ? 'selected' : '' ?>>CDI (durée indéterminée)</option>
    </select>
</div>                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Heures de travail / semaine *</label>
                        <select name="heures_travail" class="form-control" required>
                            <?php foreach ([20, 25, 30, 35, 39, 40] as $h): ?>
                                <option value="<?= $h ?>" <?= (int) $old('heures_travail', 40) === $h ? 'selected' : '' ?>><?= $h ?> h</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Date de début *</label>
                        <input type="date" name="date_debut" class="form-control" required
                               value="<?= esc($old('date_debut', date('Y-m-d'))) ?>">
                    </div>
                </div>
<div class="col-md-6">
    <div class="form-group">
        <label class="control-label">
            Date de fin <span id="date-fin-required" class="text-danger">*</span>
        </label>
        <input type="date" name="date_fin" id="date_fin" class="form-control"
               value="<?= esc($old('date_fin')) ?>">
        <small class="text-muted">
            <i class="fa fa-info-circle"></i>
            Requise pour un <strong>Essai</strong> ou un <strong>CDD</strong>. Laisser vide pour un CDI.
        </small>
    </div>
</div>            </div>

            <hr>

            <h4><i class="fa fa-money"></i> Rémunération</h4>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Salaire proposé</label>
                        <input type="number" step="0.01" name="salaire_propose" class="form-control"
                               value="<?= esc($old('salaire_propose', $c['salaire_base'])) ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Salaire négocié * (max : <?= number_format($c['salaire_max'], 2) ?>)</label>
                        <input type="number" step="0.01" name="salaire_negocie" class="form-control" required
                               min="<?= esc($c['salaire_base']) ?>" max="<?= esc($c['salaire_max']) ?>"
                               value="<?= esc($old('salaire_negocie', $c['salaire_base'])) ?>">
                    </div>
                </div>
            </div>

            <hr>

            <h4><i class="fa fa-check-square-o"></i> Options du contrat</h4>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Télétravail autorisé ?</label>
                        <select name="teletravail" class="form-control">
                            <option value="non" <?= $old('teletravail', 0) == 0 ? 'selected' : '' ?>>Non</option>
                            <option value="oui" <?= $old('teletravail') == 1 ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Période d'essai ?</label>
                        <select name="periode_essai" class="form-control">
                            <option value="non" <?= $old('periode_essai', 0) == 0 ? 'selected' : '' ?>>Non</option>
                            <option value="oui" <?= $old('periode_essai') == 1 ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Heures supplémentaires autorisées ?</label>
                        <select name="heures_sup" class="form-control">
                            <option value="non" <?= $old('heures_sup', 0) == 0 ? 'selected' : '' ?>>Non</option>
                            <option value="oui" <?= $old('heures_sup') == 1 ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Mutuelle santé ?</label>
                        <select name="mutuelle" class="form-control">
                            <option value="non" <?= $old('mutuelle', 0) == 0 ? 'selected' : '' ?>>Non</option>
                            <option value="oui" <?= $old('mutuelle') == 1 ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label">Avantages / Notes</label>
                <textarea name="avantages" rows="3" class="form-control"
                          placeholder="Ex : ticket restaurant, prime annuelle, véhicule de fonction..."><?= esc($old('avantages')) ?></textarea>
            </div>

            <hr>

            <div class="form-group text-right">
                <a href="<?= base_url('contrats') ?>" class="btn btn-default">
                    <i class="fa fa-times"></i> Annuler
                </a>
                <button type="submit" class="btn btn-warning">
                    <i class="fa fa-save"></i> Enregistrer le contrat
                </button>
            </div>
        </form>
    </div>
</div>


<script>
function toggleDateFin() {
    var type = document.getElementById('type_contrat').value;
    var dateFin = document.getElementById('date_fin');
    var req = document.getElementById('date-fin-required');

    if (type === 'CDI' || type === '') {
        dateFin.required = false;
        dateFin.value = '';
        req.style.display = 'none';
    } else {
        // Essai ou CDD → date de fin obligatoire
        dateFin.required = true;
        req.style.display = 'inline';
    }
}
// Initialisation au chargement (utile en mode édition)
document.addEventListener('DOMContentLoaded', toggleDateFin);
</script>
</body>
</html>