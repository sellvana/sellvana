<!DOCTYPE html>
<html>
<head>
    <?php echo $this->hook('head') ?>
    <?php //echo $this->view('head')->import('less')->import('css')->import('js', array('lib', 'js', 'slick')) ?>
    <style>
.wrapper { margin-top:0; width:100%; }
.site-nav-container { background:none; padding-top:0; }
    </style>
</head>
<body class="<?php echo $this->bodyClass ?>">
    <div class="wrapper">
        <?php echo $this->hook('header') ?>
        <?php echo $this->hook('main') ?>
    </div>
</body>
</html>