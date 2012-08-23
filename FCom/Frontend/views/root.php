<!DOCTYPE html>
<html <?php echo $this->getHtmlAttributes() ?>>
    <head>
        <?php echo $this->hook('head') ?>
    </head>
    <body class="<?php echo $this->getBodyClass() ?>">
        <div class="wrapper page-container">

            <?php echo $this->hook('header') ?>

            <div class="main <?php echo $this->layout_class ?>">
            	<?php //echo $this->hook('breadcrumbs') ?>

<?php if ($this->show_left_col): ?>
                <div class="col-left sidebar">
                    <?php echo $this->hook('sidebar-left') ?>
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
            </div>

            <?php echo $this->hook('footer') ?>
        </div>
    </body>
</html>