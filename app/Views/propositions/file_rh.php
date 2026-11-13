<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-inbox text-warning"></i> File RH — propositions</h1>
        <p class="page-subtitle">Propositions à lancer et contre-propositions à examiner avant le DG.</p></div>
    <a href="<?= base_url('propositions') ?>" class="btn btn-default"><i class="fa fa-list"></i> Toutes les propositions</a>
</div>

<div class="app-card">
    <h4 class="app-card-title"><i class="fa fa-pencil"></i> Brouillons à vérifier et envoyer</h4>
    <?php if (empty($brouillons)): ?>
        <p class="text-muted">Aucun brouillon en attente.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Candidat</th><th>Poste</th><th>Département</th><th>Créée le</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($brouillons as $p): ?>
                    <tr>
                        <td><?= (int) $p['id'] ?></td>
                        <td><?= esc($p['prenom'] . ' ' . $p['nom']) ?><br><small class="text-muted"><?= esc($p['annonce']) ?></small></td>
                        <td><?= esc($p['poste']) ?></td>
                        <td><?= esc($p['departement']) ?></td>
                        <td><?= tf_datetime($p['date_creation']) ?></td>
                        <td class="text-right">
                            <a class="btn btn-xs btn-warning" href="<?= base_url('propositions/' . $p['id'] . '/modifier') ?>"><i class="fa fa-pencil"></i> Modifier</a>
                            <form method="post" action="<?= base_url('propositions/' . $p['id'] . '/envoyer') ?>" style="display:inline-block">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-success" onclick="return confirm('Envoyer cette proposition au candidat ?')"><i class="fa fa-send"></i> Envoyer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="app-card">
    <h4 class="app-card-title"><i class="fa fa-random"></i> Contre-propositions à examiner</h4>
    <?php if (empty($contre_propositions)): ?>
        <p class="text-muted">Aucune contre-proposition en attente de décision RH.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Candidat</th><th>Poste</th><th>Élément</th><th>Offre initiale</th><th>Contre-proposition</th><th>Motif</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($contre_propositions as $pe): ?>
                    <tr>
                        <td><?= esc($pe['prenom'] . ' ' . $pe['nom']) ?><br><small class="text-muted"><?= esc($pe['annonce']) ?></small></td>
                        <td><?= esc($pe['poste']) ?><br><small class="text-muted"><?= esc($pe['departement']) ?></small></td>
                        <td><?= esc($pe['libelle']) ?></td>
                        <td><?= esc($pe['valeur_proposee']) ?></td>
                        <td><strong><?= esc($pe['contre_valeur']) ?></strong></td>
                        <td><small><?= esc($pe['motif_candidat']) ?></small></td>
                        <td class="text-right"><a class="btn btn-xs btn-warning" href="<?= base_url('propositions/' . $pe['proposition_id']) ?>">Ouvrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
