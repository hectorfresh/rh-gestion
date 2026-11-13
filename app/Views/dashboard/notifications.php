<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header"><div><h1><i class="fa fa-bell"></i> Notifications</h1></div></div>

<div class="app-card">
    <?php if (empty($liste)): ?>
        <div class="empty-state"><i class="fa fa-bell-o"></i>Aucune notification.</div>
    <?php else: ?>
        <ul class="timeline">
            <?php foreach ($liste as $n): ?>
                <li>
                    <a href="<?= base_url($n['lien'] ?: 'notifications') ?>"><?= esc($n['message']) ?></a>
                    <small><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
