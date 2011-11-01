<!DOCTYPE html>
<html>
    <head>
        <?php echo $this->view('head') ?>
        <?php echo $this->hook('head') ?>
    </head>
    <body class="<?php echo $this->body_class ?>">
        <div class="wrapper">
            <div class="header">
                <?php echo $this->hook('header') ?>
            </div>

            <div class="main <?php echo $this->layout_class ?>">

<?php if ($this->show_left_col): ?>
            <div class="col-left sidebar">
                <?php echo $this->hook('sidebar-right') ?>
            </div>
<?php endif ?>

            <div class="col-main">
                <?php echo $this->hook('main') ?>
            </div>

<?php if ($this->show_right_col): ?>
            <div class="col-right sidebar">
                <?php echo $this->hook('sidebar-right') ?>
            </div>
<?php endif ?>

            <div class="footer">
                <?php echo $this->hook('footer') ?>
            </div>
        </div>
    </body>
</html>