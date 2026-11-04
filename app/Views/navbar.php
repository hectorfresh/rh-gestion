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

<nav class="navbar navbar-default app-navbar">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed"
                    data-toggle="collapse" data-target="#app-navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand app-brand" href="<?= base_url('/') ?>">
                <i class="fa fa-briefcase"></i> Recrutement
            </a>
        </div>

        <?php $active = $active ?? ''; ?>

        <div class="collapse navbar-collapse" id="app-navbar-collapse">
            <ul class="nav navbar-nav">


            <?php if ($user !== null ) { ?>
                <li class="<?= $active === 'accueil' ? 'active' : '' ?>">
                    <a href="<?= base_url('/accueil') ?>">
                        <i class="fa fa-home"></i> Accueil
                    </a>
                </li>
                <?php } ?>


                <?php if ($user==$dg || $user==$manager) {?>
                        <li class="<?= $active === 'creer' ? 'active' : '' ?>">
                            <a href="<?= base_url('besoins') ?>">
                                <i class="fa fa-plus-circle"></i> Créer un besoin
                            </a>
                        </li>

                <?php } ?>

                <?php if ($user==$dg || $user==$rh) { ?>

                        <li class="<?= $active === 'valider' ? 'active' : '' ?>">
                            <a href="<?= base_url('besoins/valider') ?>">
                                <i class="fa fa-check-circle"></i> Valider
                            </a>
                        </li>
  
                    <li class="<?= $active === 'annonces' ? 'active' : '' ?>">
                        <a href="<?= base_url('annonces') ?>">
                            <i class="fa fa-bullhorn"></i> Annonces
                        </a>
                    </li>

            <?php } ?>

            
            </ul>

            <ul class="nav navbar-nav navbar-right">

    <?php if ($user !== null) { ?>

        <li class="app-user-info">
            <span class="app-user-name">
                <i class="fa fa-user-circle"></i>
                <?= esc($sessionUser['nom']) ?>
            </span>
        </li>

        <li>
            <a href="<?= base_url('logout') ?>">
                <i class="fa fa-sign-out"></i> Déconnexion
            </a>
        </li>

    <?php } else { ?>

        <li>
            <a href="<?= base_url('login') ?>">
                <i class="fa fa-sign-in"></i> Connexion
            </a>
        </li>

    <?php } ?>
    
</ul>

        </div>
    </div>
</nav>