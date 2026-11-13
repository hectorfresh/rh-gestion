<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-file-text-o text-warning"></i> Frise des contrats</h1>
        <p class="page-subtitle"><?= esc($salarie['prenom'] . ' ' . $salarie['nom']) ?> — <?= esc($salarie['poste']) ?></p>
    </div>
    <a href="<?= base_url('salaries/' . $salarie['id']) ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Fiche salarié</a>
</div>

<div class="app-card">
    <h4 class="app-card-title">Progression de l’onboarding</h4>
    <?php $p = $progression; ?>
    <div class="progress">
        <div class="progress-bar progress-bar-success" style="width: <?= (int) $p['pourcentage'] ?>%"><?= (int) $p['pourcentage'] ?>%</div>
    </div>
    <p class="text-muted"><?= (int) $p['terminees'] ?> / <?= (int) $p['total'] ?> tâches terminées — statut : <?= tf_badge($p['onboarding']['statut'] ?? 'a_faire') ?></p>
    <a class="btn btn-default btn-sm" href="<?= base_url('contrats/onboarding/' . $salarie['id']) ?>"><i class="fa fa-check-square-o"></i> Gérer l’onboarding</a>
</div>

<div class="app-card">
    <h4 class="app-card-title">Historique des contrats</h4>
    <?php if (empty($contrats)): ?>
        <div class="empty-state"><i class="fa fa-file-o"></i>Aucun contrat.</div>
    <?php else: ?>
        <ul class="timeline">
            <?php foreach ($contrats as $c): ?>
                <li>
                    <strong><?= esc($c['type_contrat']) ?></strong> — <?= tf_money($c['salaire_mensuel']) ?> / mois
                    <br><?= tf_date($c['date_debut']) ?> → <?= $c['date_fin'] ? tf_date($c['date_fin']) : 'indéterminée' ?>
                    <br><?= tf_badge($c['statut']) ?>
                    <br><a class="btn btn-xs btn-default" href="<?= base_url('contrats/' . $c['id']) ?>">Détail</a>
                    <?php if (in_array($c['statut'], ['Accepte', 'Propose'], true)): ?>
                        <a class="btn btn-xs btn-warning" href="<?= base_url('contrats/' . $c['id'] . '/renouveler') ?>">Renouveler</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>