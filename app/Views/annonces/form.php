<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<?php $edition = $annonce !== null; ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-bullhorn text-warning"></i> <?= $edition ? 'Modifier l’annonce' : 'Créer une annonce' ?></h1>
        <p class="page-subtitle">Une annonce ne peut être publiée que depuis un besoin validé, et une seule fois par besoin.</p>
    </div>
    <a href="<?= base_url('admin/annonces') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Gestion des annonces</a>
</div>

<?php if (! $edition && empty($besoins)): ?>
    <div class="app-card empty-state">
        <i class="fa fa-check-circle"></i>
        Aucun besoin validé sans annonce n’est disponible.
        <br><br><a href="<?= base_url('besoins') ?>" class="btn btn-default">Voir les besoins</a>
    </div>
<?php else: ?>
<form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-7">
            <div class="app-card">
                <h4 class="app-card-title">Contenu de l’annonce</h4>

                <?php if (! $edition): ?>
                    <div class="form-group">
                        <label>Besoin validé <span class="text-danger">*</span></label>
                        <select name="besoin_id" id="besoin_id" class="form-control" required>
                            <option value="">— Choisir un besoin —</option>
                            <?php foreach ($besoins as $b): ?>
                                <option value="<?= (int) $b['id'] ?>"
                                        data-titre="Recrutement : <?= esc($b['poste'], 'attr') ?>"
                                        data-description="<?= esc((string) $b['description'], 'attr') ?>">
                                    #<?= (int) $b['id'] ?> — <?= esc($b['poste']) ?> (<?= esc($b['departement']) ?>, <?= (int) $b['nombre'] ?> poste(s))
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <p class="text-muted">
                        Besoin #<?= (int) $annonce['besoin_id'] ?> — <?= esc($annonce['poste']) ?> (<?= esc($annonce['departement']) ?>, <?= (int) $annonce['nombre'] ?> poste(s))
                    </p>
                <?php endif; ?>

                <div class="form-group">
                    <label>Titre <span class="text-danger">*</span></label>
                    <input type="text" name="titre" id="titre" class="form-control" maxlength="190" required
                           value="<?= esc(old('titre', $annonce['titre'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="6"><?= esc(old('description', $annonce['description'] ?? '')) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Profil recherché</label>
                    <textarea name="profil" class="form-control" rows="5" placeholder="Diplômes, compétences, expérience…"><?= esc(old('profil', $annonce['profil'] ?? '')) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Date limite de candidature</label>
                    <input type="date" name="date_limite" class="form-control"
                           value="<?= esc(old('date_limite', $annonce['date_limite'] ?? '')) ?>">
                    <p class="help-block">Laissez vide pour ne pas fixer de limite. La date ne peut pas être passée.</p>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="app-card">
                <h4 class="app-card-title">Publication</h4>
                <?php if ($edition): ?>
                    <p>État : <?= tf_badge((int) $annonce['est_cloture'] === 1 ? 'Clôturée' : 'Ouverte') ?></p>
                    <p>Publiée le : <?= tf_datetime($annonce['date_publication']) ?></p>
                <?php else: ?>
                    <p class="text-muted small">L’annonce sera immédiatement publiée et visible par les candidats.</p>
                <?php endif; ?>
                <button type="submit" class="btn btn-warning btn-block">
                    <i class="fa fa-paper-plane"></i> <?= $edition ? 'Enregistrer les modifications' : 'Publier l’annonce' ?>
                </button>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<script>
(function () {
    var besoin = document.getElementById('besoin_id');
    var titre = document.getElementById('titre');
    if (!besoin || !titre) return;
    besoin.addEventListener('change', function () {
        var option = besoin.options[besoin.selectedIndex];
        if (option && option.dataset.titre && titre.value.trim() === '') {
            titre.value = option.dataset.titre;
        }
    });
})();
</script>
<?= $this->endSection() ?>
