<?php $user = session()->get('user'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h3><?php  echo $user['nom']; ?></h3>
    <h3><?php  echo $user['role_id']; ?></h3>
    <h3><?php  echo $user['email']; ?></h3>
</body>
</html>


