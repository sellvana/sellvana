<header class="adm-page-title">
    <span class="title">Product Fields</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="location.href='<?php echo $this->BApp->href('indextank/product_fields/form/')?>'"><span>New Product Field</span></button>
    </div>
</header>

<?php if (!$this->status):?>
    <h3>Warning: IndexDen API URL isn't set</h3>
    <h3>Please visit <a href="<?=$this->BApp->href('settings')?>?tab=FCom_IndexTank">setting page</a> to setup API URL</h3>
<?php endif; ?>

<?php echo $this->view('jqgrid') ?>

<?php echo $this->view('indextank/control_index_dialog')->set('status', $this->status) ?>
