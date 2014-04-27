<!DOCTYPE html>
<html lang="en">
    <head>
        <?= $this->hook( 'head' ) ?>
    </head>
    <body class="<?= $this->body_class ? join( ' ', (array)$this->body_class ) : '' ?>">
        <div class="container">

            <?php if ( $this->errors ): ?>
                <?php foreach ( $this->errors as $error ): ?>
                    <div class="alert alert-danger">
                        <?= $this->q( $error ) ?>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
            <?= $this->view( 'core/messages' )->set( 'namespace', 'install' ) ?>

            <div class="panel">
                <?= $this->hook( 'main' ) ?>
            </div>
        </div>
    </body>
</html>
