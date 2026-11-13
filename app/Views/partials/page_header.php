<?php
$header = $page_header ?? [];
$titre = $header['titre'] ?? ($titre ?? 'TalentFlow');
$sousTitre = $header['sous_titre'] ?? ($sous_titre ?? null);
$fil = $header['fil'] ?? [];
$pastilles = $header['pastilles'] ?? [];
$actions = $header['actions'] ?? [];
?>
<div class="app-page-header">
    <div class="app-page-header-left">
        <?php if ($fil): ?>
            <nav class="app-breadcrumb">
                <?php foreach ($fil as $libelle => $url): ?>
                    <?php if ($url): ?>
                        <a href="<?= base_url($url) ?>"><?= esc($libelle) ?></a>
                    <?php else: ?>
                        <span><?= esc($libelle) ?></span>
                    <?php endif; ?>
                    <?php if ($libelle !== array_key_last($fil)): ?>
                        <i class="fa fa-angle-right"></i>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        <h1><?= esc($titre) ?></h1>
        <?php if ($sousTitre): ?>
            <p class="page-subtitle"><?= esc($sousTitre) ?></p>
        <?php endif; ?>
        <?php if ($pastilles): ?>
            <div class="app-page-badges">
                <?php foreach ($pastilles as $pastille): ?>
                    <span class="status-badge <?= esc($pastille['classe'] ?? 'status-gray') ?>"><?= esc($pastille['libelle'] ?? '') ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($actions): ?>
        <div class="app-page-actions">
            <?php foreach ($actions as $action): ?>
                <a href="<?= base_url($action['url'] ?? '#') ?>" class="btn <?= esc($action['classe'] ?? 'btn-default') ?>">
                    <?php if (! empty($action['icone'])): ?><i class="fa <?= esc($action['icone']) ?>"></i><?php endif; ?>
                    <?= esc($action['libelle'] ?? '') ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>