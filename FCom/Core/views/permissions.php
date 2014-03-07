<?php /* Leave as PHP, renderers are not available yet at this point */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="<?=BRequest::i()->webRoot().'/FCom/Core/css/bootstrap-3.min.css'?>" />
    <link rel="stylesheet" type="text/css" href="<?=BRequest::i()->webRoot().'/FCom/Core/css/fcom.core.css'?>" />
</head>
<body>
    <div class="container container-low">
        <div class="panel panel-danger">
            <div class="panel-heading">
                <h3 class="panel-title"><?=$this->_('Permissions error')?></h3>
            </div>

            <p>
                <?=$this->_('Before proceeding, please make sure that the following folders are writable for web service:')?>
                <?php foreach ($this->errors as $error): ?>
                    <div class="alert alert-danger well well-small"><?=$error?></div>
                <?php endforeach ?>
            </p>
        </div>
    </div>
</body>
</html>
