<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>creer compte</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">

</head>
<body>
    <body class="templatemo-bg-gray">
	<h1 class="margin-bottom-15">Create Account</h1>
	<div class="container">
		<div class="col-md-12">			
			<form class="form-horizontal templatemo-create-account templatemo-container" role="form" action="<?= base_url('create_account') ?>" method="post">
    <?= csrf_field() ?>

    <?php if (session()->getFlashdata('error')) { ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php } ?>

    <div class="form-inner">
        <div class="form-group">
            <div class="col-md-6">
                <label for="nom" class="control-label">Nom</label>
                <input type="text" class="form-control" id="nom" name="nom" value="<?= esc(old('nom')) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-12">
                <label for="email" class="control-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= esc(old('email')) ?>" required>
                <input type="hidden" name="role_id" value="4">
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-6">
                <label for="password" class="control-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
        </div>
        <div class="form-group">
			          <div class="col-md-12">
			            <input type="submit" value="Create account" class="btn btn-info">
			            <a href="<?= base_url('/login') ?>" class="pull-right">Login</a>
			          </div>
			        </div>	
				</div>				    	
		      </form>		      
		</div>
	</div>

    
</body>
</html>