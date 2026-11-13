<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="login-wrap">
    <div class="app-card">
        <h3 class="text-center"><i class="fa fa-user-plus text-warning"></i> Inscription candidat</h3>
        <p class="text-center text-muted">Créez votre compte pour postuler aux annonces.</p>

        <form method="post" action="<?= base_url('inscription') ?>">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom" class="form-control" required value="<?= esc(old('nom')) ?>">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" class="form-control" value="<?= esc(old('prenom')) ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required value="<?= esc(old('email')) ?>">
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="telephone" class="form-control" value="<?= esc(old('telephone')) ?>">
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input type="password" name="mdp" class="form-control" required>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label>Confirmation</label>
                        <input type="password" name="mdp2" class="form-control" required>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-warning btn-block">
                <i class="fa fa-user-plus"></i> Créer mon compte
            </button>
        </form>

        <hr>
        <p class="text-center small text-muted">
            Déjà inscrit ? <a href="<?= base_url('login') ?>">Se connecter</a>
        </p>
    </div>
</div>
<?= $this->endSection() ?>
