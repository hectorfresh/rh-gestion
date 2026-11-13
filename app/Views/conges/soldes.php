<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-list text-warning"></i> Soldes de congés <?= date('Y') ?></h1>
        <p class="page-subtitle">Crédit automatique : 2,5 jours par mois écoulé, plafond 30 jours.</p></div>
    <a href="<?= base_url('conges/validation') ?>" class="btn btn-default"><i class="fa fa-check-square"></i> Demandes</a>
</div>

<div class="app-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Matricule</th><th>Salarié</th><th>Département</th><th>Congé payé</th><th>Maladie</th><th>Sans solde</th></tr></thead>
            <tbody>
            <?php foreach ($salaries as $s): ?>
                <tr>
                    <td><?= esc($s['matricule']) ?></td>
                    <td><?= esc($s['prenom'] . ' ' . $s['nom']) ?></td>
                    <td><?= esc($s['departement']) ?></td>
                    <?php foreach ($s['soldes'] as $solde): ?>
                        <?php if (in_array($solde['libelle'], ['Congé payé', 'Congé maladie', 'Congé sans solde'], true)): ?>
                            <td>
                                <strong><?= number_format((float) $solde['disponible'], 1, ',', ' ') ?> j</strong><br>
                                <small class="text-muted"><?= number_format((float) $solde['jours_acquis'], 1, ',', ' ') ?> acquis · <?= number_format((float) $solde['jours_pris'], 1, ',', ' ') ?> pris</small>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
