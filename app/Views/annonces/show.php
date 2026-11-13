<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<?php
$fermee = (int) $annonce['est_cloture'] === 1
    || (! empty($annonce['date_limite']) && $annonce['date_limite'] < date('Y-m-d'));
?>
<div class="app-page-header">
    <div>
        <h1><?= esc($annonce['titre']) ?></h1>
        <p class="page-subtitle"><?= esc($annonce['poste']) ?> — <?= esc($annonce['departement']) ?></p>
    </div>
    <a href="<?= base_url('annonces') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Toutes les annonces</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="app-card">
            <h4 class="app-card-title">Description du poste</h4>
            <p><?= nl2br(esc($annonce['description'] ?: 'Description à venir.')) ?></p>
            <?php if ($annonce['besoin_description']): ?>
                <h5 class="text-muted">Contexte du besoin</h5>
                <p><?= nl2br(esc($annonce['besoin_description'])) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="app-card">
            <h4 class="app-card-title">Informations</h4>
            <p><strong>Postes :</strong> <?= (int) $annonce['nombre'] ?></p>
            <p><strong>Salaire :</strong> <span class="money"><?= tf_money($annonce['salaire_estime'] ?: $annonce['salaire_base']) ?></span> / mois</p>
            <p><strong>Fourchette :</strong> <?= tf_money($annonce['salaire_base']) ?> – <?= tf_money($annonce['salaire_max']) ?></p>
            <p><strong>Publiée le :</strong> <?= tf_date($annonce['date_publication']) ?></p>
            <?php if ($annonce['date_limite']): ?><p><strong>Date limite :</strong> <?= tf_date($annonce['date_limite']) ?></p><?php endif; ?>
            <?php if ($fermee): ?>
                <span class="status-badge status-gray">Annonce clôturée</span>
            <?php elseif ($ma_candidature): ?>
                <div class="alert alert-info" style="margin-top:15px">Vous avez déjà postulé à cette annonce.</div>
                <a href="<?= base_url('candidatures/' . $ma_candidature['id']) ?>" class="btn btn-default btn-block">Suivre ma candidature</a>
            <?php elseif ($role === 'candidat'): ?>
                <a href="<?= base_url('annonces/' . $annonce['id'] . '/postuler') ?>" class="btn btn-warning btn-block"><i class="fa fa-paper-plane"></i> Postuler</a>
            <?php elseif (! $utilisateur): ?>
                <a href="<?= base_url('login') ?>" class="btn btn-warning btn-block">Se connecter pour postuler</a>
            <?php else: ?>
                <p class="text-muted small">Seuls les comptes candidats peuvent postuler.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
