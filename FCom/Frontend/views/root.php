<!DOCTYPE html>
<html <?php echo $this->getHtmlAttributes() ?>>
    <head>
        <?php echo $this->hook('head') ?>
    </head>
    <body class="<?php echo $this->getBodyClass() ?>">
        <div class="page-wrapper">
            <?php echo $this->hook('header') ?>
            <div class="main <?php echo $this->layout_class ?>">
            	<?php echo $this->hook('breadcrumbs') ?>
<?php if ($this->show_left_col): ?>
                <aside class="col-left sidebar">
                    <?php echo $this->hook('sidebar-left') ?>
                </aside>
<?php endif ?>
                <div class="col-main">
                    <?php echo $this->hook('main') ?>
                </div>
<?php if ($this->show_right_col): ?>
                <aside class="col-right sidebar">
                    <?php echo $this->hook('sidebar-right') ?>
                </aside>
<?php endif ?>
            </div>
            <?php echo $this->hook('footer') ?>
        </div>
    </body>
</html>