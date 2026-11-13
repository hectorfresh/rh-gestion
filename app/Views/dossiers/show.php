<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-folder-open text-warning"></i> Dossier de <?= esc($salarie['prenom'] . ' ' . $salarie['nom']) ?></h1>
        <p class="page-subtitle"><?= esc($salarie['matricule']) ?> — <?= esc($salarie['poste']) ?></p>
    </div>
    <div>
        <a href="<?= base_url('salaries/' . $salarie['id']) ?>" class="btn btn-default"><i class="fa fa-id-card"></i> Fiche salarié</a>
        <a href="<?= base_url('dossiers') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="app-card">
            <h4 class="app-card-title">Documents classés</h4>
            <?php if (empty($documents)): ?>
                <div class="empty-state"><i class="fa fa-folder-o"></i>Aucun document dans ce dossier.</div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead><tr><th>Type</th><th>Fichier</th><th>Émission</th><th>Expiration</th><th>Validité</th><th>Taille</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= esc($doc['type_document']) ?></td>
                            <td><a href="<?= base_url('documents/' . $doc['id'] . '/download') ?>"><i class="fa fa-download"></i> <?= esc($doc['nom_original']) ?></a></td>
                            <td><?= tf_date($doc['date_emission']) ?></td>
                            <td><?= tf_date($doc['date_expiration']) ?></td>
                            <td><?= tf_badge($doc['statut_validite']) ?></td>
                            <td><?= number_format((int) $doc['taille'] / 1024, 1, ',', ' ') ?> Ko</td>
                            <td class="text-right">
                                <form method="post" action="<?= base_url('documents/' . $doc['id'] . '/supprimer') ?>" style="display:inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-danger" onclick="return confirm('Supprimer ce document ?')"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-5">
        <div class="app-card">
            <h4 class="app-card-title"><i class="fa fa-upload"></i> Ajouter un document</h4>
            <form method="post" action="<?= base_url('dossiers/' . $salarie['id'] . '/documents') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Type de document</label>
                    <select name="type_document_id" class="form-control">
                        <?php foreach ($types as $type): ?><option value="<?= (int) $type['id'] ?>"><?= esc($type['libelle']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fichier (5 Mo max)</label>
                    <input type="file" name="fichier" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                    <small class="help-block">Formats : PDF, JPG, PNG, DOC, DOCX.</small>
                </div>
                <div class="form-group">
                    <label>Date d’émission</label>
                    <input type="date" name="date_emission" class="form-control">
                </div>
                <div class="form-group">
                    <label>Date d’expiration</label>
                    <input type="date" name="date_expiration" class="form-control">
                </div>
                <button class="btn btn-warning btn-block"><i class="fa fa-upload"></i> Téléverser</button>
            </form>
        </div>

        <div class="app-card">
            <h4 class="app-card-title">Informations</h4>
            <p><strong>Matricule :</strong> <?= esc($salarie['matricule']) ?></p>
            <p><strong>Département :</strong> <?= esc($salarie['departement']) ?></p>
            <p><strong>Poste :</strong> <?= esc($salarie['poste']) ?></p>
            <p><strong>Embauche :</strong> <?= tf_date($salarie['date_embauche']) ?></p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
