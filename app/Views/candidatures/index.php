<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-file-text-o text-warning"></i> Suivi des candidatures</h1>
        <p class="page-subtitle">Filtrer par annonce et par statut, puis ouvrir chaque dossier.</p></div>
    <a href="<?= base_url('admin/annonces') ?>" class="btn btn-default"><i class="fa fa-bullhorn"></i> Gérer les annonces</a>
</div>

<div class="app-card">
    <form method="get" action="<?= base_url('candidatures') ?>" class="form-inline" style="margin-bottom:16px">
        <div class="form-group">
            <label class="sr-only" for="annonce_id">Annonce</label>
            <select name="annonce_id" id="annonce_id" class="form-control">
                <option value="0">Toutes les annonces</option>
                <?php foreach ($annonces as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (int) $annonce_filtre === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= esc($a['titre']) ?> — <?= esc($a['poste']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="sr-only" for="statut">Statut</label>
            <select name="statut" id="statut" class="form-control">
                <option value="">Tous les statuts</option>
                <?php foreach ($statuts as $s): ?>
                    <option value="<?= esc($s) ?>" <?= $statut_filtre === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-warning"><i class="fa fa-filter"></i> Filtrer</button>
        <?php if ($statut_filtre !== '' || $annonce_filtre > 0): ?>
            <a href="<?= base_url('candidatures') ?>" class="btn btn-default">Réinitialiser</a>
        <?php endif; ?>
    </form>

    <p class="text-muted"><?= (int) $total ?> candidature(s) — page <?= (int) $page ?> / <?= (int) $nb_pages ?>.</p>

    <?php if (empty($candidatures)): ?>
        <div class="empty-state"><i class="fa fa-inbox"></i>Aucune candidature pour ces critères.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Candidat</th><th>Annonce / poste</th><th>Département</th><th>Entretiens</th><th>Statut</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($candidatures as $c): ?>
                    <tr>
                        <td><?= (int) $c['id'] ?></td>
                        <td><strong><?= esc($c['prenom'] . ' ' . $c['nom']) ?></strong><br><small class="text-muted"><?= esc($c['email']) ?></small></td>
                        <td><?= esc($c['annonce']) ?><br><small class="text-muted"><?= esc($c['poste']) ?></small></td>
                        <td><?= esc($c['departement']) ?></td>
                        <td><?= (int) $c['nb_entretiens'] ?></td>
                        <td><?= tf_badge($c['statut']) ?></td>
                        <td><?= tf_date($c['date_candidature']) ?></td>
                        <td class="text-right"><a class="btn btn-xs btn-default" href="<?= base_url('candidatures/' . $c['id']) ?>">Dossier</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($nb_pages > 1): ?>
            <?php
            $query = static function (int $p) use ($statut_filtre, $annonce_filtre): string {
                return base_url('candidatures?' . http_build_query([
                    'statut'     => $statut_filtre,
                    'annonce_id' => $annonce_filtre,
                    'page'       => $p,
                ]));
            };
            ?>
            <nav>
                <ul class="pagination">
                    <li class="<?= $page <= 1 ? 'disabled' : '' ?>"><a href="<?= $query(max(1, $page - 1)) ?>">&laquo;</a></li>
                    <?php for ($p = max(1, $page - 2); $p <= min($nb_pages, $page + 2); $p++): ?>
                        <li class="<?= $p === $page ? 'active' : '' ?>"><a href="<?= $query($p) ?>"><?= $p ?></a></li>
                    <?php endfor; ?>
                    <li class="<?= $page >= $nb_pages ? 'disabled' : '' ?>"><a href="<?= $query(min($nb_pages, $page + 1)) ?>">&raquo;</a></li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
