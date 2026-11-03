<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
	<link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
	<link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
	<link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
	<link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">

    <!-- <link rel="stylesheet" href="../../public/assets/css">
    <link rel="stylesheet" href="../../public/assets/*"> -->

</head>
<body class="templatemo-bg-gray">
    <div class="container">
        <div class="col-md-12">
        <h1 class="margin-bottom-15">Login Form One</h1>
        
        <form class="form-horizontal templatemo-container templatemo-login-form-1 margin-bottom-30" role="form" action="/login" method="post">
        <?= csrf_field() ?>
            <div class="form-group">
            <div class="col-xs-12">		            
            <div class="control-wrapper">
        
            <label for="email" class="control-label fa-label"><i class="fa fa-user fa-medium"></i></label>
            <input type="email"name="email" class="form-control" id="email" placeholder="email">
            </div>
            </div>
            </div>

            <div class="form-group">
		          <div class="col-md-12">
		          	<div class="control-wrapper">

        		            	<label for="password" class="control-label fa-label"><i class="fa fa-lock fa-medium"></i></label>
                                <input type="password" name="mdp" class="form-control" id="password" placeholder="Password">
            </div>
            </div>
            </div>

            <div class="form-group">
		          <div class="col-md-12">
		          	<div class="control-wrapper">
		          		<input type="submit" value="Connexion" class="btn btn-info">
		          		<a href="#" class="text-right pull-right">Forgot password?</a>
		          	</div>
		          </div>
		        </div>

            <hr>
		        <div class="form-group">
		        	<div class="col-md-12">
		        		<label>Login with: </label>
		        		<div class="inline-block">
		        			<a href="#"><i class="fa fa-facebook-square login-with"></i></a>
			        		<a href="#"><i class="fa fa-twitter-square login-with"></i></a>
			        		<a href="#"><i class="fa fa-google-plus-square login-with"></i></a>
			        		<a href="#"><i class="fa fa-tumblr-square login-with"></i></a>
			        		<a href="#"><i class="fa fa-github-square login-with"></i></a>
		        		</div>		        		
		        	</div>
		        </div>
		      </form>
		      <div class="text-center">
		      	<a href="#" class="templatemo-create-new">Create new account <i class="fa fa-arrow-circle-o-right"></i></a>	
		      </div>
        </div>
    </div>


</body>
</html>