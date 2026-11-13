<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-cogs text-warning"></i> Gestion des annonces</h1>
        <p class="page-subtitle">Créer, modifier, suivre et clôturer les annonces issues de besoins validés.</p></div>
    <div>
        <a href="<?= base_url('admin/annonces/creer') ?>" class="btn btn-warning"><i class="fa fa-plus"></i> Créer une annonce</a>
        <a href="<?= base_url('besoins') ?>" class="btn btn-default"><i class="fa fa-clipboard"></i> Besoins validés</a>
    </div>
</div>

<div class="app-card">
    <?php if (empty($annonces)): ?>
        <div class="empty-state"><i class="fa fa-bullhorn"></i>Aucune annonce. Publiez-en depuis un besoin validé.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Titre</th><th>Poste</th><th>Département</th><th>Candidatures</th><th>Retenus</th><th>État</th><th>Publiée</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($annonces as $a): ?>
                    <tr>
                        <td><?= (int) $a['id'] ?></td>
                        <td><a href="<?= base_url('annonces/' . $a['id']) ?>"><?= esc($a['titre']) ?></a></td>
                        <td><?= esc($a['poste']) ?></td>
                        <td><?= esc($a['departement']) ?></td>
                        <td><?= (int) $a['nb_candidatures'] ?> / <?= (int) $a['nombre'] ?></td>
                        <td><?= (int) $a['nb_retenues'] ?> / <?= (int) $a['nombre'] ?></td>
                        <td>
                            <?= tf_badge((int) $a['est_cloture'] === 1 ? 'Clôturée' : 'Ouverte') ?>
                            <?php if (! empty($a['date_cloture'])): ?>
                                <br><small class="text-muted"><?= tf_datetime($a['date_cloture']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= tf_date($a['date_publication']) ?></td>
                        <td class="text-right">
                            <a class="btn btn-xs btn-default" href="<?= base_url('admin/annonces/' . $a['id'] . '/modifier') ?>">Modifier</a>
                            <?php if ((int) $a['est_cloture'] === 0): ?>
                                <form method="post" action="<?= base_url('annonces/' . $a['id'] . '/cloturer') ?>" style="display:inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-default" onclick="return confirm('Clôturer cette annonce ?')">Clôturer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
