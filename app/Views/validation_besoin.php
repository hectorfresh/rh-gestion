<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="table-responsive">
        <h4 class="margin-bottom-15">Validation Des Besoins</h4>
               
        <table class="table table-striped table-hover table-bordered">
        <thead>
        <tr>
            <th>Numero</th>
            <th>Poste</th>
            <th>Action</th>
        </tr>

        </thead>



        <tbody>


            <?php foreach ($besoins as $b) {?>
                        <tr>
                            <td><a href="/besoins/<?php echo $b['id'];?>/"><?php echo $b['id'];?></a></td>
                            <td><a href="/besoins/<?php echo $b['id'];?>/"><?php echo $b['libelle'];?></a></td>
                            <td>
                                <div class="btn-group">
                          <button type="button" class="btn btn-info">Action</button>
                          <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span><tbody></tbody>
                            <span class="sr-only">Toggle Dropdown</span>
                          </button>
                          <ul class="dropdown-menu" role="menu">
                            <li><a href="/besoins/valider/<?php echo $b['id'];?>">Valider</a></li>
                            <li><a href="/besoins/refuser/<?php echo $b['id'];?>">Refuser</a></li>
                          </ul>
                        </div>
                                
                            </td>
                        </tr>

            <?php } ?>

        </tbody>
    </table>
    </div>
</body>
</html>