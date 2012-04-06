<?php
$p = $this->model;
$tabs = $this->sortedTabs();
?>
<script>
head(function() {
    window.adminForm = Admin.form({
        tabs:     '.adm-tabs-left li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo BApp::href('cms/blocks/form/'.$p->id) ?>',
        url_post: '<?php echo BApp::href('cms/blocks/form/'.$p->id) ?>'
    });
})
</script>
<form action="<?php echo BApp::href('cms/blocks/form/'.$p->id) ?>" method="post">
    <input type="hidden" id="tab" name="tab" value="<?=$this->cur_tab?>"/>
    <header class="adm-page-title">
        <span class="title"><?php echo $p->id ? 'Edit CMS Block: '.$this->q($p->handle) : 'Create New CMS Block' ?></span>
        <div style="float:right">
            <button class="st1 sz2 btn" onclick="adminForm.saveAll()"><span><?php echo BLocale::_('Save')?></span></button>
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