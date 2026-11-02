<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h3>Génération Annonces</h3>
    <form action="/annonces/creer" method="post">
        <p>
            Besoin 
            <select name="besoin">
                
                <?php foreach ($besoins as $b) { ?>
                        <option value="<?php echo $b->id; ?>"><?php echo $b->libelle; ?></option>

                    <?php } ?>
            </select>
        </p>
        <p>Description </p>
        <textarea name="desce">
            
        </textarea>
        <p><input type="submit" value="Generer"></p>
    </form>
</body>
</html>