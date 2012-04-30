<?php
$p = $this->model;
$tabs = $this->sortedTabs();
$formUrl = BApp::href('indextank/product_functions/form/?id='.$p->id);
?>
<script>
head(function() {
    window.adminForm = FCom.Admin.form({
        tabs:     '.adm-tabs-left li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo $formUrl ?>',
        url_post: '<?php echo $formUrl ?>'
    });
})
</script>
<?php //echo $this->messagesHtml() ?>
<form action="<?php echo $formUrl ?>" method="post">
    <input type="hidden" id="tab" name="tab" value="<?=$this->cur_tab?>"/>
    <header class="adm-page-title">
        <span class="title"><?php echo $p->id ? 'Edit Product Field: '.$this->q($p->field_name) : 'Create New Product Field' ?></span>
        <div style="float:right">
            <button class="st1 sz2 btn" onclick="adminForm.saveAll()"><span><?php echo BLocale::_('Save')?></span></button>
            <button class="st1 sz2 btn" onclick="javascript:history.back()"><span><?php echo BLocale::_('Cancel')?></span></button>
        </div>
    </header>

    <section class="adm-content-box info-view-mode">
        <div class="adm-content-inner">
            <div class="adm-tabs-left-bg"></div>
            <nav class="adm-tabs-left">
                <ul>
    <?php foreach ($tabs as $k=>$tab): ?>
                    <li <?php if ($k===$this->cur_tab): ?>class="active"<?php endif ?>>
                        <a href="#tab-<?php echo $this->q($k) ?>"><span class="icon"></span><?php echo $this->q($tab['label']) ?></a>
                    </li>
    <?php endforeach ?>
                </ul>
            </nav>
            <div class="adm-tabs-container">
    <?php foreach ($tabs as $k=>$tab): ?>
                <section id="tab-<?php echo $this->q($k) ?>" class="adm-tabs-content"
                    <?php if ($k!==$this->cur_tab): ?>hidden<?php endif ?>
                    <?php if (empty($tab['async'])): ?>data-loaded="true"<?php endif ?>
                >
    <?php if (empty($tab['async'])) echo $this->view($tab['view']) ?>
                </section>
    <?php endforeach ?>
            </div>
        </div>
    </section>
</form>
