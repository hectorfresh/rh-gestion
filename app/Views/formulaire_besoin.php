


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h3>Creation De Besoin</h3>  

    <form action="/besoins/creer" method="post">
        <?= csrf_field() ?>
        <p>
            Poste 
            <select name="poste">
                <?php foreach ($postes as $p) {?>
                    <option value="<?php echo $p['id']; ?>"><?php echo $p['libelle']; ?></option>
                <?php } ?>

            </select>
        </p>

        <p>Nombre <input type="number" name="nombre"></p>
        <p>
            Description
    
        </p>
                    <textarea name="description">

            </textarea>

        <p><input type="submit" value="Valider"></p>

    </form> 
</body>
</html>