<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h3>Validation Des Besoins</h3>
    <table border="1">
        <tr>
            <th>Numero</th>
            <th>Poste</th>
            <th>Action</th>
        </tr>


            <?php foreach ($besoins as $b) {?>
                        <tr>
                            <td><a href="/besoins/<?php echo $b['id'];?>/"><?php echo $b['id'];?></a></td>
                            <td><a href="/besoins/<?php echo $b['id'];?>/"><?php echo $b['libelle'];?></a></td>
                            <td>
                                <a href="/besoins/valider/<?php echo $b['id'];?>">Valider</a>
                                <a href="/besoins/refuser/<?php echo $b['id'];?>">Refuser</a>
                            </td>
                        </tr>

            <?php } ?>


    </table>
</body>
</html>