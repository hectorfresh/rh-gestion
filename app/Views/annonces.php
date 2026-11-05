<?php 

use App\Models\RoleModel;

$roleModel = new RoleModel();

$dg      = $roleModel->where('libelle', 'dg')->first()['id'] ?? null;
$rh      = $roleModel->where('libelle', 'rh')->first()['id'] ?? null;
$manager = $roleModel->where('libelle', 'manager')->first()['id'] ?? null;



// $user = session()->get('user')['role_id'];
$sessionUser = session()->get('user');
$user = $sessionUser['role_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres d'emploi</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">

    <style>
        .page-subtitle {
            color: #888;
            margin: -10px 0 30px;
            font-size: 14px;
        }
        
        /* Style de la carte d'annonce */
        .annonce-card {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%; /* Force la même hauteur pour toutes les cartes */
            display: flex;
            flex-direction: column;
            border-top: 4px solid #f0ad4e; /* Liseré orange en haut */
            margin-bottom: 25px;
        }
        .annonce-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .annonce-icon {
            width: 50px;
            height: 50px;
            background: #fdf0d5;
            color: #f0ad4e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 15px;
        }
        
        .annonce-card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 700;
            color: #333;
            line-height: 1.3;
        }
        
        .annonce-salaire {
            color: #5cb85c;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 20px;
            background: #e5f5e5;
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        /* Pousse le bouton tout en bas de la carte */
        .annonce-footer {
            margin-top: auto;
            border-top: 1px solid #f0f0f0;
            padding-top: 15px;
        }
    </style>
</head>
<body class="templatemo-bg-gray">

    <?= $this->include('navbar', ['active' => 'annonces']) ?>

    <div class="container">
        <div class="col-md-12">
            <h1 class="text-center margin-bottom-15">Offres d'emploi</h1>
            <p class="text-center page-subtitle">
                Découvrez les postes ouverts et postulez dès maintenant.
            </p>

            <div class="row">
                <!-- Boucle sur les annonces -->
                <?php foreach ($annonces as $a) { ?>
                    
                    <!-- Colonne Bootstrap : 3 par ligne sur PC (col-md-4) -->
                    <div class="col-md-4 col-sm-6">
                        <div class="annonce-card">
                            
                            <div class="annonce-icon">
                                <i class="fa fa-briefcase"></i>
                            </div>
                            
                            <h3><?= esc($a->libelle) ?></h3>
                            
                            <div>
                                <span class="annonce-salaire">
                                    <i class="fa fa-money"></i> Salaire de base : <?= esc($a->salaire_base) ?>
                                </span>
                            </div>
                            
                            <?php if ($user!=$dg || $user!=$rh || $user!=$manager) { ?>
                            <div class="annonce-footer">
                                <a href="/postule/<?= esc($a->id) ?>" class="btn btn-warning btn-block">
                                    <i class="fa fa-paper-plane"></i> Postuler
                                </a>
                            </div>
                            <?php } ?>
                            
                        </div>
                    </div>

                <?php } ?>
            </div>
            
        </div>
    </div>

</body>
</html>