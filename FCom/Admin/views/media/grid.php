<?php
    $config = FCom_Admin_Controller_Media::i()->gridConfig();
    echo $this->view('jqgrid')->set('config', $config);
?>