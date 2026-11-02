<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h3>Fiche Besoin</h3>
    <p>Numero : <?php echo $besoins['id'];?></p>
    <p>Poste : <?php echo $besoins['libelle'];?></p>
    <p>Nombre : <?php echo $besoins['nombre'];?></p>
    <p>Description : <?php echo $besoins['desce'];?></p>
    <a href="/besoins/valider/<?php echo $besoins['id'];?>">Valider</a>
    <a href="/besoins/refuser/<?php echo $besoins['id'];?>">Refuser</a>
    <a href="/besoins/valider">Retour a la liste</a>


</body>
</html>