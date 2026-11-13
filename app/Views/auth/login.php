<?= $this->extend('layout/base') ?>

<?= $this->section('content') ?>
<div class="login-wrap">
    <div class="app-card">
        <h3 class="text-center"><i class="fa fa-sign-in text-warning"></i> Connexion</h3>
        <p class="text-center text-muted">Accédez à votre espace TalentFlow.</p>

        <form method="post" action="<?= base_url('login') ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required
                       value="<?= esc(old('email')) ?>" placeholder="vous@exemple.com">
            </div>
            <div class="form-group">
                <label for="mdp">Mot de passe</label>
                <input type="password" class="form-control" id="mdp" name="mdp" required placeholder="••••••">
            </div>
            <button type="submit" class="btn btn-warning btn-block">
                <i class="fa fa-sign-in"></i> Se connecter
            </button>
        </form>

        <hr>
        <p class="text-center small text-muted">
            Pas de compte ? <a href="<?= base_url('inscription') ?>">Créer un compte candidat</a>
        </p>
        <p class="text-center small text-muted">
            Démo : <code>dg@mail.com</code>, <code>rh@mail.com</code>, <code>manager@mail.com</code>,
            <code>employe@mail.com</code>, <code>candidat@mail.com</code> — mot de passe <code>1234</code>.
        </p>
    </div>
</div>
<?= $this->endSection() ?>
