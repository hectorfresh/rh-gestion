<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-id-card text-warning"></i> <?= esc($salarie['prenom'] . ' ' . $salarie['nom']) ?></h1>
        <p class="page-subtitle"><?= esc($salarie['matricule']) ?> — <?= esc($salarie['poste']) ?> (<?= esc($salarie['departement']) ?>)</p>
    </div>
    <div>
        <?php if ($peut_modifier ?? false): ?>
            <a href="<?= base_url('salaries/' . $salarie['id'] . '/modifier') ?>" class="btn btn-warning"><i class="fa fa-pencil"></i> Modifier</a>
        <?php endif; ?>
        <?php if (can_show('dossiers')): ?>
            <a href="<?= base_url('dossiers/' . $salarie['id']) ?>" class="btn btn-warning"><i class="fa fa-folder-open"></i> Dossier & GED</a>
        <?php endif; ?>
        <a href="<?= base_url('salaries') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
    </div>
</div>

<ul class="nav nav-tabs" style="margin-bottom:15px">
    <li class="active"><a href="#identite" data-toggle="tab">Identité</a></li>
    <li><a href="#contrats" data-toggle="tab">Contrats</a></li>
    <li><a href="#affectations" data-toggle="tab">Affectations</a></li>
    <li><a href="#documents" data-toggle="tab">Documents</a></li>
    <li><a href="#carriere" data-toggle="tab">Carrière</a></li>
    <li><a href="#conges" data-toggle="tab">Congés</a></li>
    <li><a href="#pointage" data-toggle="tab">Pointage</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="identite">
        <div class="app-card">
            <div class="row">
                <div class="col-sm-6">
                    <p><strong>Nom :</strong> <?= esc($salarie['nom']) ?></p>
                    <p><strong>Prénom :</strong> <?= esc($salarie['prenom']) ?></p>
                    <p><strong>Email :</strong> <?= esc($salarie['email']) ?></p>
                    <p><strong>Téléphone :</strong> <?= esc($salarie['telephone'] ?: '—') ?></p>
                    <p><strong>Adresse :</strong> <?= esc($salarie['adresse'] ?: '—') ?></p>
                    <p><strong>Ville :</strong> <?= esc($salarie['ville'] ?: '—') ?></p>
                </div>
                <div class="col-sm-6">
                    <p><strong>Matricule :</strong> <?= esc($salarie['matricule']) ?></p>
                    <p><strong>Date d’embauche :</strong> <?= tf_date($salarie['date_embauche']) ?></p>
                    <p><strong>Département :</strong> <?= esc($salarie['departement']) ?></p>
                    <p><strong>Poste :</strong> <?= esc($salarie['poste']) ?></p>
                    <p><strong>Manager :</strong> <?= esc(trim(($salarie['manager_prenom'] ?? '') . ' ' . ($salarie['manager_nom'] ?? '')) ?: '—') ?></p>
                    <p><strong>État :</strong> <?= tf_badge((int) $salarie['actif'] === 1 ? 'Actif' : 'Inactif') ?></p>
                    <?php if ($peut_voir_salaire ?? false): ?>
                        <?php $contratActif = null; foreach ($contrats as $c) { if ($c['statut'] === 'Accepte') { $contratActif = $c; break; } } ?>
                        <?php if ($contratActif): ?>
                            <p><strong>Salaire mensuel :</strong> <span class="money"><?= tf_money($contratActif['salaire_mensuel']) ?></span></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane" id="contrats">
        <div class="app-card">
            <table class="table table-condensed">
                <thead><tr><th>Type</th><th>Salaire</th><th>Début</th><th>Fin</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($contrats as $c): ?>
                    <tr>
                        <td><?= esc($c['type_contrat']) ?></td>
                        <td class="money"><?= $peut_voir_salaire ? tf_money($c['salaire_mensuel']) : '—' ?></td>
                        <td><?= tf_date($c['date_debut']) ?></td>
                        <td><?= tf_date($c['date_fin']) ?></td>
                        <td><?= tf_badge($c['statut']) ?></td>
                        <td><a class="btn btn-xs btn-default" href="<?= base_url('contrats/' . $c['id']) ?>">Voir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane" id="affectations">
        <div class="app-card">
            <table class="table table-condensed">
                <thead><tr><th>Date</th><th>Département</th><th>Poste</th><th>Motif</th></tr></thead>
                <tbody>
                <?php foreach ($affectations as $a): ?>
                    <tr><td><?= tf_date($a['date_debut']) ?></td><td><?= esc($a['departement']) ?></td><td><?= esc($a['poste']) ?></td><td><?= esc($a['motif'] ?: '—') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane" id="documents">
        <div class="app-card">
            <?php if (empty($documents)): ?>
                <p class="text-muted">Aucun document.</p>
            <?php else: ?>
                <table class="table table-condensed">
                    <thead><tr><th>Type</th><th>Fichier</th><th>Émission</th><th>Expiration</th><th>Validité</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= esc($doc['type_document']) ?></td>
                            <td><a href="<?= base_url('documents/' . $doc['id'] . '/download') ?>"><?= esc($doc['nom_original']) ?></a></td>
                            <td><?= tf_date($doc['date_emission']) ?></td>
                            <td><?= tf_date($doc['date_expiration']) ?></td>
                            <td><?= tf_badge($doc['statut_validite']) ?></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <?php if (can_show('dossiers')): ?>
                <a class="btn btn-default btn-sm" href="<?= base_url('dossiers/' . $salarie['id']) ?>">Gérer le dossier</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-pane" id="carriere">
        <div class="app-card">
            <table class="table table-condensed">
                <thead><tr><th>Date</th><th>Événement</th><th>Ancien poste</th><th>Nouveau poste</th><th>Ancien salaire</th><th>Nouveau salaire</th><th>Commentaire</th></tr></thead>
                <tbody>
                <?php foreach ($historique as $h): ?>
                    <tr>
                        <td><?= tf_date($h['date_evenement']) ?></td>
                        <td><?= esc($h['type_evenement']) ?></td>
                        <td><?= esc($h['ancien_poste'] ?: '—') ?></td>
                        <td><?= esc($h['nouveau_poste'] ?: '—') ?></td>
                        <td class="money"><?= $peut_voir_salaire ? tf_money($h['ancien_salaire']) : '—' ?></td>
                        <td class="money"><?= $peut_voir_salaire ? tf_money($h['nouveau_salaire']) : '—' ?></td>
                        <td><?= esc($h['commentaire'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane" id="conges">
        <div class="app-card">
            <?php foreach ($soldes as $solde): ?>
                <p>
                    <strong><?= esc($solde['libelle']) ?></strong> :
                    <?= number_format((float) $solde['jours_acquis'], 1, ',', ' ') ?> acquis,
                    <?= number_format((float) $solde['jours_pris'], 1, ',', ' ') ?> pris,
                    <strong><?= number_format((float) $solde['disponible'], 1, ',', ' ') ?> j disponibles</strong>
                </p>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="tab-pane" id="pointage">
        <div class="app-card">
            <table class="table table-condensed">
                <thead><tr><th>Jour</th><th>Entrée</th><th>Sortie</th><th>Minutes</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($pointages as $p): ?>
                    <tr>
                        <td><?= tf_date($p['jour']) ?></td>
                        <td><?= esc(substr((string) $p['heure_entree'], 0, 5) ?: '—') ?></td>
                        <td><?= esc(substr((string) $p['heure_sortie'], 0, 5) ?: '—') ?></td>
                        <td><?= (int) $p['minutes_travaillees'] ?></td>
                        <td><?= tf_badge($p['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>