<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="app-page-header">
    <div>
        <h1><i class="fa fa-paper-plane text-warning"></i> Postuler</h1>
        <p class="page-subtitle"><?= esc($annonce['titre']) ?> — <?= esc($annonce['poste']) ?></p>
    </div>
    <a href="<?= base_url('annonces/' . $annonce['id']) ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<form method="post" action="<?= base_url('annonces/' . $annonce['id'] . '/postuler') ?>" id="form-candidature">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-8">
            <div class="app-card">
                <h4 class="app-card-title"><i class="fa fa-file-text-o"></i> Mon CV en 4 étapes</h4>

                <?php if (! empty($cvs)): ?>
                    <div class="form-group">
                        <label>Réutiliser un CV existant</label>
                        <select name="cv_id" id="cv_id" class="form-control">
                            <option value="0">— Créer un nouveau CV —</option>
                            <?php foreach ($cvs as $cv): ?>
                                <option value="<?= (int) $cv['id'] ?>"><?= esc($cv['titre']) ?> (<?= tf_date($cv['created_at']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-muted small">Si vous réutilisez un CV, vous pouvez passer directement à la lettre de motivation.</p>
                    <hr>
                <?php endif; ?>

                <ul class="nav nav-pills cv-wizard-nav" id="cv-etapes">
                    <li class="active"><a href="#etape-identite" data-step="1"><span class="badge">1</span> Identité</a></li>
                    <li><a href="#etape-formation" data-step="2"><span class="badge">2</span> Formation</a></li>
                    <li><a href="#etape-experience" data-step="3"><span class="badge">3</span> Expérience</a></li>
                    <li><a href="#etape-langues" data-step="4"><span class="badge">4</span> Langues</a></li>
                </ul>

                <div class="cv-step" data-step="1">
                    <div class="form-group">
                        <label>Titre du CV <span class="text-danger">*</span></label>
                        <input type="text" name="titre_cv" id="titre_cv" class="form-control" maxlength="150"
                               value="<?= esc(old('titre_cv', 'CV — ' . $utilisateur['prenom'] . ' ' . $utilisateur['nom'])) ?>">
                    </div>
                    <div class="form-group">
                        <label>Présentation</label>
                        <textarea name="description_cv" class="form-control" rows="3"><?= esc(old('description_cv')) ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-sm-6"><div class="form-group"><label>Nom complet</label>
                            <input type="text" name="nom" class="form-control" value="<?= esc(old('nom', $utilisateur['prenom'] . ' ' . $utilisateur['nom'])) ?>"></div></div>
                        <div class="col-sm-6"><div class="form-group"><label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?= esc(old('email', $utilisateur['email'])) ?>"></div></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6"><div class="form-group"><label>Téléphone</label>
                            <input type="text" name="telephone" class="form-control" value="<?= esc(old('telephone', $utilisateur['telephone'] ?? '')) ?>"></div></div>
                        <div class="col-sm-6"><div class="form-group"><label>Ville</label>
                            <input type="text" name="ville" class="form-control" value="<?= esc(old('ville')) ?>"></div></div>
                    </div>
                    <div class="form-group"><label>Adresse</label>
                        <input type="text" name="adresse" class="form-control" value="<?= esc(old('adresse')) ?>"></div>
                </div>

                <div class="cv-step" data-step="2" style="display:none">
                    <?php foreach ([0, 1] as $i): ?>
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>Formation <?= $i + 1 ?></strong></div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-sm-6"><div class="form-group"><label>Diplôme</label>
                                        <input type="text" name="formations[<?= $i ?>][diplome]" class="form-control"></div></div>
                                    <div class="col-sm-6"><div class="form-group"><label>Établissement</label>
                                        <input type="text" name="formations[<?= $i ?>][etablissement]" class="form-control"></div></div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6"><div class="form-group"><label>Début</label>
                                        <input type="date" name="formations[<?= $i ?>][date_debut]" class="form-control"></div></div>
                                    <div class="col-sm-6"><div class="form-group"><label>Fin</label>
                                        <input type="date" name="formations[<?= $i ?>][date_fin]" class="form-control"></div></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cv-step" data-step="3" style="display:none">
                    <?php foreach ([0, 1] as $i): ?>
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>Expérience <?= $i + 1 ?></strong></div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-sm-6"><div class="form-group"><label>Poste occupé</label>
                                        <input type="text" name="experiences[<?= $i ?>][poste]" class="form-control"></div></div>
                                    <div class="col-sm-6"><div class="form-group"><label>Entreprise</label>
                                        <input type="text" name="experiences[<?= $i ?>][entreprise]" class="form-control"></div></div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6"><div class="form-group"><label>Début</label>
                                        <input type="date" name="experiences[<?= $i ?>][date_debut]" class="form-control"></div></div>
                                    <div class="col-sm-6"><div class="form-group"><label>Fin</label>
                                        <input type="date" name="experiences[<?= $i ?>][date_fin]" class="form-control"></div></div>
                                </div>
                                <div class="form-group"><label>Description</label>
                                    <textarea name="experiences[<?= $i ?>][description]" class="form-control" rows="2"></textarea></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cv-step" data-step="4" style="display:none">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="row">
                            <div class="col-sm-6"><div class="form-group">
                                <label>Langue <?= $i + 1 ?></label>
                                <select name="langues[<?= $i ?>][langue_id]" class="form-control">
                                    <option value="">—</option>
                                    <?php foreach ($langues as $langue): ?>
                                        <option value="<?= (int) $langue['id'] ?>"><?= esc($langue['libelle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div></div>
                            <div class="col-sm-6"><div class="form-group">
                                <label>Niveau</label>
                                <select name="langues[<?= $i ?>][niveau_id]" class="form-control">
                                    <option value="">—</option>
                                    <?php foreach ($niveaux as $niveau): ?>
                                        <option value="<?= (int) $niveau['id'] ?>"><?= esc($niveau['libelle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="cv-wizard-actions">
                    <button type="button" class="btn btn-default" id="cv-precedent" disabled><i class="fa fa-arrow-left"></i> Précédent</button>
                    <button type="button" class="btn btn-warning" id="cv-suivant">Suivant <i class="fa fa-arrow-right"></i></button>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="app-card">
                <h4 class="app-card-title">Lettre de motivation</h4>
                <div class="form-group">
                    <textarea name="lettre_motivation" class="form-control" rows="10" placeholder="Expliquez votre motivation…"><?= esc(old('lettre_motivation')) ?></textarea>
                </div>
                <button type="submit" class="btn btn-warning btn-block" id="cv-envoyer"><i class="fa fa-paper-plane"></i> Envoyer ma candidature</button>
                <p class="help-block text-muted small">Une seule candidature est possible par annonce.</p>
            </div>
        </div>
    </div>
</form>

<style>
.cv-wizard-nav { margin-bottom: 20px; }
.cv-wizard-nav > li > a { border-radius: 4px; margin-right: 4px; }
.cv-wizard-nav .badge { background: #d9dee5; color: #52606d; }
.cv-wizard-nav li.active .badge { background: #d98300; color: #fff; }
.cv-wizard-actions { margin-top: 18px; display: flex; justify-content: space-between; }
</style>

<script>
(function () {
    var etapes = Array.prototype.slice.call(document.querySelectorAll('.cv-step'));
    var liens = Array.prototype.slice.call(document.querySelectorAll('#cv-etapes a'));
    var precedent = document.getElementById('cv-precedent');
    var suivant = document.getElementById('cv-suivant');
    var cvExistant = document.getElementById('cv_id');
    var titreCv = document.getElementById('titre_cv');
    var courante = 1;
    var total = etapes.length;

    function afficher(etape) {
        courante = Math.max(1, Math.min(total, etape));
        etapes.forEach(function (bloc) {
            bloc.style.display = parseInt(bloc.dataset.step, 10) === courante ? '' : 'none';
        });
        liens.forEach(function (lien) {
            lien.parentNode.classList.toggle('active', parseInt(lien.dataset.step, 10) === courante);
        });
        precedent.disabled = courante === 1;
        suivant.style.display = courante === total ? 'none' : '';
        if (titreCv) {
            titreCv.required = !cvExistant || cvExistant.value === '0';
        }
    }

    liens.forEach(function (lien) {
        lien.addEventListener('click', function (event) {
            event.preventDefault();
            afficher(parseInt(lien.dataset.step, 10));
        });
    });
    precedent.addEventListener('click', function () { afficher(courante - 1); });
    suivant.addEventListener('click', function () { afficher(courante + 1); });

    if (cvExistant) {
        cvExistant.addEventListener('change', function () {
            var creation = cvExistant.value === '0';
            document.querySelectorAll('.cv-step').forEach(function (bloc) {
                if (bloc.dataset.step !== '1') {
                    bloc.style.display = creation ? (parseInt(bloc.dataset.step, 10) === courante ? '' : 'none') : 'none';
                }
            });
            document.querySelectorAll('#cv-etapes, .cv-wizard-actions').forEach(function (el) {
                el.style.display = creation ? '' : 'none';
            });
            if (titreCv) titreCv.required = creation;
        });
    }
    afficher(1);
})();
</script>
<?= $this->endSection() ?>
