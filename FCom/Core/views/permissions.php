<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="<?=BRequest::i()->webRoot().'/FCom/Core/css/bootstrap.min.css'?>" />
</head>
<body>
    <h1>Permissions error</h1>

    <p>Before proceeding, please make sure that the following folders are writable for web service:
    <ul>
    <?php foreach ($this->errors as $error): ?>
        <li><?=$error?></li>
    <?php endforeach ?>
    </ul>
    </p>
</body>
</html>
