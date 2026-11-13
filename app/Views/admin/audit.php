<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div><h1><i class="fa fa-history text-warning"></i> Journal d’audit</h1>
        <p class="page-subtitle">Traçabilité des actions sensibles.</p></div>
    <a href="<?= base_url('admin') ?>" class="btn btn-default"><i class="fa fa-cogs"></i> Administration</a>
</div>

<div class="app-card">
    <form method="get" class="form-inline" style="margin-bottom:15px">
        <select name="utilisateur_id" class="form-control input-sm">
            <option value="">— Utilisateur —</option>
            <?php foreach ($utilisateurs as $u): ?>
                <option value="<?= (int) $u['id'] ?>" <?= (int) $utilisateur_id === (int) $u['id'] ? 'selected' : '' ?>>
                    <?= esc(trim($u['prenom'] . ' ' . $u['nom'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="action" class="form-control input-sm" placeholder="Action" value="<?= esc($action) ?>">
        <input type="text" name="entite" class="form-control input-sm" placeholder="Entité" value="<?= esc($entite) ?>">
        <input type="date" name="date_debut" class="form-control input-sm" value="<?= esc($date_debut) ?>">
        <input type="date" name="date_fin" class="form-control input-sm" value="<?= esc($date_fin) ?>">
        <button class="btn btn-sm btn-warning">Filtrer</button>
        <a href="<?= base_url('audit') ?>" class="btn btn-sm btn-default">Réinitialiser</a>
    </form>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Entité</th><th>ID</th><th>Détails</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= tf_datetime($log['created_at']) ?></td>
                    <td><?= esc(trim(($log['prenom'] ?? '') . ' ' . ($log['nom'] ?? '')) ?: 'Système') ?></td>
                    <td><code><?= esc($log['action']) ?></code></td>
                    <td><?= esc($log['entite']) ?></td>
                    <td><?= $log['entite_id'] !== null ? (int) $log['entite_id'] : '—' ?></td>
                    <td><small><?= esc(mb_strimwidth((string) $log['details_json'], 0, 120, '…')) ?></small></td>
                    <td><small><?= esc($log['ip'] ?: '—') ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
