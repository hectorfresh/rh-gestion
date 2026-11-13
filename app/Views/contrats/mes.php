<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-file-text-o text-warning"></i> Mes contrats</h1>
        <p class="page-subtitle">Vos contrats de travail.</p></div>
</div>

<div class="app-card">
    <?php if (empty($contrats)): ?>
        <div class="empty-state"><i class="fa fa-file-o"></i>Aucun contrat pour le moment.</div>
    <?php else: ?>
        <?php foreach ($contrats as $c): ?>
            <div class="element-row">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="element-libelle"><?= esc($c['poste']) ?> — <?= esc($c['type_contrat']) ?></div>
                        <p class="text-muted" style="margin:5px 0 0">
                            <?= tf_money($c['salaire_mensuel']) ?> / mois ·
                            du <?= tf_date($c['date_debut']) ?> au <?= tf_date($c['date_fin']) ?>
                        </p>
                    </div>
                    <div class="col-sm-4 text-right">
                        <?= tf_badge($c['statut']) ?><br><br>
                        <a class="btn btn-sm btn-default" href="<?= base_url('contrats/' . $c['id']) ?>">Détail</a>
                        <a class="btn btn-sm btn-warning" href="<?= base_url('contrats/' . $c['id'] . '/pdf') ?>"><i class="fa fa-download"></i> PDF</a>
                        <a class="btn btn-sm btn-default" href="<?= base_url('contrats/onboarding/' . $c['salarie_id']) ?>"><i class="fa fa-check-square-o"></i> Onboarding</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
