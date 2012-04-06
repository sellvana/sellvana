<?php
    $m = $this->model;
    $baseHref = BApp::m('FCom_Admin')->baseHref();
?>
<script>
head(function() {
    window.adminForm = Admin.form({
        form:     '#roles-form',
        tabs:     '.adm-tabs-left li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo $baseHref.'/roles/form/'.$m->id ?>',
        url_post: '<?php echo $baseHref.'/roles/form/'.$m->id ?>'
    });
});
</script>
<form id="users-form" action="<?php echo $baseHref.'/roles/form/'.$m->id ?>" method="post">
    <header class="adm-page-title">
        <span class="title" id="tab-title">
            <?php echo $this->mode==='create' ? 'Create New Role' : $this->q($m->role_name) ?>
        </span>
        <div class="btns-set">
            <button type="button" class="st2 sz2 btn" onclick="location.href='<?php echo BApp::href('roles')?>'" type="button"><span>Back to list</span></button>
            <button type="submit" class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>Save All</span></button>
        </div>
    </header>
    <section class="adm-content-box info-view-mode">
        <div class="adm-content-inner">
            <div class="adm-tabs-left-bg"></div>
            <nav class="adm-tabs-left">
                <ul>
    <?php foreach ($this->tabs as $k=>$tab): if (!empty($tab['disabled'])) continue; ?>
                    <li <?php if ($k===$this->cur_tab): ?>class="active"<?php endif ?>>
                        <a href="#tab-<?php echo $this->q($k) ?>"><span class="icon"></span><?php echo $this->q($tab['label']) ?></a>
                    </li>
    <?php endforeach ?>
                </ul>
            </nav>
            <div class="adm-tabs-container">
    <?php foreach ($this->tabs as $k=>$tab): ?>
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
<script>
head(function() { $("#roles-form").validationEngine(); });
</script>