<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-file-text-o text-warning"></i> Contrat #<?= (int) $contrat['id'] ?></h1>
        <p class="page-subtitle"><?= esc(trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? ''))) ?> — <?= esc($contrat['poste'] ?? '') ?></p>
    </div>
    <div>
        <a href="<?= base_url('contrats/' . $contrat['id'] . '/pdf') ?>" class="btn btn-warning"><i class="fa fa-download"></i> Télécharger le PDF</a>
        <a href="<?= base_url('contrats/onboarding/' . $contrat['salarie_id']) ?>" class="btn btn-default"><i class="fa fa-check-square-o"></i> Onboarding</a>
        <a href="<?= $staff ? base_url('contrats') : base_url('contrats/mes') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
    </div>
</div>

<div class="app-card">
    <div class="row">
        <div class="col-md-6">
            <h4 class="app-card-title">Informations du contrat</h4>
            <table class="table table-condensed">
                <tr><th>Type</th><td><?= esc($contrat['type_contrat']) ?></td></tr>
                <tr><th>Statut</th><td><?= tf_badge($contrat['statut']) ?></td></tr>
                <tr><th>Salaire mensuel</th><td class="money"><?= tf_money($contrat['salaire_mensuel']) ?></td></tr>
                <tr><th>Date de début</th><td><?= tf_date($contrat['date_debut']) ?></td></tr>
                <tr><th>Date de fin</th><td><?= tf_date($contrat['date_fin']) ?></td></tr>
                <tr><th>Période d’essai</th><td><?= (int) $contrat['periode_essai_mois'] ?> mois</td></tr>
                <tr><th>Horaires</th><td><?= substr($contrat['heure_debut'], 0, 5) ?> – <?= substr($contrat['heure_fin'], 0, 5) ?></td></tr>
                <tr><th>Régime</th><td><?= esc($contrat['regime'] ?? '—') ?> (<?= (float) ($contrat['heures_hebdomadaires'] ?? 0) ?> h/sem.)</td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <h4 class="app-card-title">Salarié</h4>
            <table class="table table-condensed">
                <tr><th>Nom</th><td><?= esc(trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? ''))) ?></td></tr>
                <tr><th>Email</th><td><?= esc($contrat['email'] ?? '—') ?></td></tr>
                <tr><th>Matricule</th><td><?= esc($contrat['matricule'] ?? '—') ?></td></tr>
                <tr><th>Poste</th><td><?= esc($contrat['poste'] ?? '—') ?></td></tr>
                <tr><th>Département</th><td><?= esc($contrat['departement'] ?? '—') ?></td></tr>
                <tr><th>Date d’embauche</th><td><?= tf_date($contrat['date_embauche'] ?? null) ?></td></tr>
                <tr><th>Créé le</th><td><?= tf_datetime($contrat['date_creation']) ?></td></tr>
            </table>
        </div>
    </div>

    <?php if (($peut_rompre ?? false) && $contrat['statut'] === 'Accepte'): ?>
        <hr>
        <form method="post" action="<?= base_url('contrats/' . $contrat['id'] . '/rompre') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-danger btn-sm" onclick="return confirm('Marquer ce contrat comme rompu ?')">
                <i class="fa fa-times"></i> Rompre le contrat
            </button>
        </form>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
