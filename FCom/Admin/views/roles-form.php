<?php
    $m = $this->model;
    $formUrl = BApp::href('roles/form/?id='.$m->id);
?>
<script>
head(function() {
    window.adminForm = Admin.form({
        form:     '#roles-form',
        tabs:     '.adm-tabs-sidebar li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo $formUrl ?>',
        url_post: '<?php echo $formUrl ?>'
    });
    $("#roles-form").validate();
});
</script>
<form id="users-form" action="<?php echo $formUrl ?>" method="post">
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
    	<aside class="adm-sidebar">
            <nav class="adm-tabs-sidebar">
                <ul>
    <?php foreach ($this->tabs as $k=>$tab): if (!empty($tab['disabled'])) continue; ?>
                    <li <?php if ($k===$this->cur_tab): ?>class="active"<?php endif ?>>
                        <a href="#tab-<?php echo $this->q($k) ?>"><span class="icon"></span><?php echo $this->q($tab['label']) ?></a>
                    </li>
    <?php endforeach ?>
                </ul>
            </nav>
		</aside>
        <div class="adm-main">
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