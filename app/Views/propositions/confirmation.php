<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<?php
$valeurs = [];
foreach ($elements as $element) {
    $valeur = $element['valeur_finale'];
    if ($element['statut'] === 'contre_validee' && $element['contre_valeur'] !== null) {
        $valeur = $element['contre_valeur'];
    }
    $valeurs[$element['code']] = $valeur ?? $element['valeur_proposee'];
}
?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-check-circle text-success"></i> Confirmation de l’embauche</h1>
        <p class="page-subtitle"><?= esc($proposition['prenom'] . ' ' . $proposition['nom']) ?> — <?= esc($proposition['poste']) ?> (<?= esc($proposition['departement']) ?>)</p>
    </div>
    <a href="<?= base_url('propositions/' . $proposition['id']) ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour à la proposition</a>
</div>

<div class="alert alert-success">
    <i class="fa fa-check-circle"></i> Tous les éléments sont acceptés. La confirmation est définitive et génère le contrat dans une transaction.
</div>

<div class="app-card">
    <h4 class="app-card-title">Récapitulatif des valeurs finales</h4>
    <table class="table table-bordered">
        <thead><tr><th>Élément</th><th>Valeur proposée</th><th>Valeur finale</th><th>Origine</th></tr></thead>
        <tbody>
        <?php foreach ($elements as $element): ?>
            <tr>
                <td><?= esc($element['libelle']) ?></td>
                <td><?= esc($element['valeur_proposee']) ?></td>
                <td><strong><?= esc($valeurs[$element['code']] ?? '') ?></strong></td>
                <td>
                    <?php if ($element['statut'] === 'contre_validee'): ?>
                        <span class="status-badge status-green">Contre-proposition validée par le DG</span>
                    <?php else: ?>
                        <span class="status-badge status-green">Offre initiale acceptée</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="app-card text-center">
    <h4>Confirmer et générer mon contrat</h4>
    <p class="text-muted">Création du contrat, de la fiche salarié, de l’affectation, de l’onboarding et du crédit de congés. En cas d’erreur, toutes les étapes sont annulées.</p>
    <form method="post" action="<?= base_url('propositions/' . $proposition['id'] . '/confirmer') ?>" id="form-confirmation">
        <?= csrf_field() ?>
        <button class="btn btn-success btn-lg" id="btn-confirmer" onclick="return confirm('Confirmer définitivement cette proposition ?')">
            <i class="fa fa-check-circle"></i> Confirmer et générer mon contrat
        </button>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('form-confirmation');
    var bouton = document.getElementById('btn-confirmer');
    if (! form || ! bouton) return;
    form.addEventListener('submit', function () {
        bouton.disabled = true;
        bouton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Confirmation en cours…';
    });
})();
</script>
<?= $this->endSection() ?>
