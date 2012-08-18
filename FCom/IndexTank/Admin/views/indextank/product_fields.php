<header class="adm-page-title">
    <span class="title">Product Fields</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="location.href='<?php echo BApp::href('indextank/product_fields/form/')?>'"><span>New Product Field</span></button>
    </div>
</header>

<?php if(!$this->status):?>
    <h3>Warning: IndexDen API URL isn't set</h3>
    <h3>Please visit <a href="/admin/settings#tab-FCom_IndexTank">setting page</a> to setup API URL</h3>
<?php else:?>
<h3>Index <?=$this->status['name']?> (created at <?=date("Y-m-d", strtotime($this->status['date']))?>)
        Status: <?=$this->status['status']?>
</h3>
<h3>Size: <?=$this->status['size']?> documents</h3>
<h3>Indexing: <?=$this->indexing_status?></h3>
<?php endif; ?>

<?php echo $this->view('jqgrid') ?>

<?php echo $this->view('indextank/control_index_dialog') ?>
