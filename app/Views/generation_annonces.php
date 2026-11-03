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

</head>
<body class="templatemo-bg-gray">

	<div class="container">
		<div class="col-md-12">	
			<h1 class="text-center margin-bottom-15">Génération Annonces</h1>
    
    		<form class="form-horizontal templatemo-contact-form-2 templatemo-container" role="form" action="/annonces/creer" method="post">
                <?= csrf_field() ?>

                <div class="row">
					<div class="col-md-6">
						<div class="form-group">				          		          	
				           	<div class="col-sm-12">
				            	<div class="templatemo-input-icon-container">
				            		<i class="fa fa-info-circle"></i>
                                    <select name="besoin" class="form-control">
                                        <option value="">besoin..</option>
                                        <?php foreach ($besoins as $b) { ?>
                                            <option value="<?php echo $b->id; ?>"><?php echo $b->libelle; ?></option>
                                        <?php } ?>
                                    </select>
				            	</div>		            		            		            
				          	</div>              
				        </div>
					</div> <!-- close col-md-6 (besoin) -->

					<div class="col-md-6">
						<div class="form-group">
				          <div class="col-md-12">
				            <div class="templatemo-input-icon-container">
				            	<i class="fa fa-pencil-square-o"></i>
				            	<textarea rows="10" cols="50" class="form-control" id="description" placeholder="description" name="desce"></textarea>
				            </div>
				          </div>
				        </div>
					</div>					
				</div>	        
		        <div class="form-group">
		          <div class="col-md-12">
		            <div class="checkbox pull-left">
		                <label>
		                  <input type="checkbox"> Send a copy to my email.
		                </label>
		            </div>
		            <input type="submit" value="Valider" class="btn btn-warning pull-right">		            
		          </div>
		        </div>		    	
		      </form>	
              
              <div class="row">
              	<div class="col-md-8 col-xs-offset-1">
                	<br>
                	<p>Contact form two is brought to you by <a href="#">templatemo.com</a> that can be used for your websites.</p>
                </div>
              </div>

		</div>
	</div>
</body>
</html>