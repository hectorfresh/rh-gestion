<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="/login" method="post">
        <?= csrf_field() ?>
        <p>Email <input type="email" name="email"></p>
        <p>Mot de Passe <input type="password" name="mdp"></p>
        <p><input type="submit" value="Connexion"></p>
    </form>
</body>
</html>