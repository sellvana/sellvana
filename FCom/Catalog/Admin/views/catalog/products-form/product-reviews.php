<?php
$m = $this->model;
$prodCtrl = FCom_ProductReviews_Admin_Controller::i();
?>
<div id="linked-products-layout">
    <div class="ui-layout-west">
        <div class="group-container">
        </div>
    </div>
    <div class="ui-layout-center">
        <?=$this->view('jqgrid')->set('config', $prodCtrl->gridConfig($m)) ?>
    </div>
</div>