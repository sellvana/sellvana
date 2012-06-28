<?php
$m = $this->model;
$prodCtrl = FCom_Catalog_Admin_Controller_ProductReviews::i();
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