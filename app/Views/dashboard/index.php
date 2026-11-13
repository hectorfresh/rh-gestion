<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-home text-warning"></i> Bonjour <?= esc($utilisateur['prenom'] ?: $utilisateur['nom']) ?></h1>
        <p class="page-subtitle">Votre espace <?= esc($role) ?> dans TalentFlow.</p>
    </div>
</div>

<?php if ($role === 'candidat'): ?>
    <div class="row">
        <div class="col-md-4 col-sm-6">
            <div class="app-stat blue"><i class="fa fa-bullhorn stat-icon"></i>
                <div class="stat-label">Annonces ouvertes</div><div class="stat-value"><?= (int) $nb_annonces ?></div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="app-stat"><i class="fa fa-file-text-o stat-icon"></i>
                <div class="stat-label">Mes candidatures</div><div class="stat-value"><?= (int) ($mes_candidatures ?? 0) ?></div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="app-stat green"><i class="fa fa-handshake-o stat-icon"></i>
                <div class="stat-label">Mes propositions</div><div class="stat-value"><?= (int) ($mes_propositions ?? 0) ?></div>
            </div>
        </div>
    </div>

    <div class="app-card">
        <h4 class="app-card-title"><i class="fa fa-file-text-o"></i> Mes dernières candidatures</h4>
        <?php if (empty($dernieres_candidatures)): ?>
            <div class="empty-state"><i class="fa fa-inbox"></i>Aucune candidature pour le moment.<br><br>
                <a class="btn btn-warning" href="<?= base_url('annonces') ?>">Voir les annonces</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Annonce</th><th>Date</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($dernieres_candidatures as $c): ?>
                        <tr>
                            <td><?= esc($c['titre']) ?></td>
                            <td><?= date('d/m/Y', strtotime($c['date_candidature'])) ?></td>
                            <td><span class="status-badge status-blue"><?= esc($c['statut']) ?></span></td>
                            <td class="text-right"><a class="btn btn-xs btn-default" href="<?= base_url('candidatures/' . $c['id']) ?>">Détail</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (in_array($role, ['employe', 'manager', 'rh', 'dg'], true)): ?>
    <div class="row">
        <div class="col-md-4 col-sm-6">
            <div class="app-stat green"><i class="fa fa-plane stat-icon"></i>
                <div class="stat-label">Solde congé payé <?= date('Y') ?></div>
                <div class="stat-value"><?= number_format((float) ($solde_conge['disponible'] ?? 0), 1, ',', ' ') ?> j</div>
                <small class="text-muted"><?= number_format((float) ($solde_conge['jours_acquis'] ?? 0), 1, ',', ' ') ?> acquis − <?= number_format((float) ($solde_conge['jours_pris'] ?? 0), 1, ',', ' ') ?> pris</small>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="app-stat blue"><i class="fa fa-clock-o stat-icon"></i>
                <div class="stat-label">Pointage du jour</div>
                <div class="stat-value" style="font-size:18px">
                    <?php if (! empty($mon_pointage['heure_entree'])): ?>
                        <?= date('H:i', strtotime($mon_pointage['heure_entree'])) ?>
                        <?= ! empty($mon_pointage['heure_sortie']) ? ' → ' . date('H:i', strtotime($mon_pointage['heure_sortie'])) : ' (en cours)' ?>
                    <?php else: ?>Non pointé<?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="app-stat"><i class="fa fa-list stat-icon"></i>
                <div class="stat-label">Mes demandes de congé</div><div class="stat-value"><?= (int) ($mes_conges ?? 0) ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($role === 'manager'): ?>
    <div class="row">
        <div class="col-md-6"><div class="app-stat"><i class="fa fa-plus-circle stat-icon"></i>
            <div class="stat-label">Mes besoins exprimés</div><div class="stat-value"><?= (int) ($besoins_manager ?? 0) ?></div>
        </div></div>
        <div class="col-md-6"><div class="app-stat red"><i class="fa fa-check-square stat-icon"></i>
            <div class="stat-label">Congés à valider (équipe)</div><div class="stat-value"><?= (int) ($conges_manager ?? 0) ?></div>
        </div></div>
    </div>
<?php endif; ?>

<?php if (in_array($role, ['rh', 'dg'], true)): ?>
    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="app-stat"><i class="fa fa-clipboard stat-icon"></i>
            <div class="stat-label">Besoins en attente RH</div><div class="stat-value"><?= (int) $besoins_rh ?></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="app-stat orange"><i class="fa fa-gavel stat-icon"></i>
            <div class="stat-label">Besoins en attente DG</div><div class="stat-value"><?= (int) $besoins_dg ?></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="app-stat blue"><i class="fa fa-users stat-icon"></i>
            <div class="stat-label">Salariés actifs</div><div class="stat-value"><?= (int) $salaries_actifs ?></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="app-stat green"><i class="fa fa-file-text-o stat-icon"></i>
            <div class="stat-label">Contrats actifs</div><div class="stat-value"><?= (int) $contrats_actifs ?></div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-tasks"></i> Actions en attente</h4>
                <p><a href="<?= base_url('besoins/file-rh') ?>">Besoins à valider (RH)</a> <span class="badge"><?= (int) $besoins_rh ?></span></p>
                <p><a href="<?= base_url('besoins/file-dg') ?>">Besoins à trancher (DG)</a> <span class="badge"><?= (int) $besoins_dg ?></span></p>
                <p><a href="<?= base_url('candidatures') ?>">Nouvelles candidatures</a> <span class="badge"><?= (int) $candidatures_nouvelles ?></span></p>
                <p><a href="<?= base_url('conges/validation') ?>">Congés à traiter</a> <span class="badge"><?= (int) $conges_rh + (int) $conges_manager ?></span></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-money"></i> Budgets <?= date('Y') ?></h4>
                <table class="table table-condensed">
                    <thead><tr><th>Département</th><th class="text-right">Budget</th><th class="text-right">Disponible</th></tr></thead>
                    <tbody>
                    <?php foreach ($budgets as $b): ?>
                        <tr>
                            <td><?= esc($b['libelle']) ?></td>
                            <td class="text-right money"><?= number_format((float) $b['montant_annuel'], 0, ',', ' ') ?></td>
                            <td class="text-right money <?= $b['resume']['disponible'] < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= number_format($b['resume']['disponible'], 0, ',', ' ') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="<?= base_url('rapports') ?>" class="btn btn-sm btn-default"><i class="fa fa-bar-chart"></i> Voir les rapports</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="app-card">
    <h4 class="app-card-title"><i class="fa fa-bell"></i> Notifications récentes</h4>
    <?php if (empty($notifications_recentes)): ?>
        <p class="text-muted">Aucune notification.</p>
    <?php else: ?>
        <ul class="timeline">
            <?php foreach ($notifications_recentes as $n): ?>
                <li>
                    <a href="<?= base_url($n['lien'] ?: 'notifications') ?>"><?= esc($n['message']) ?></a>
                    <small><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
