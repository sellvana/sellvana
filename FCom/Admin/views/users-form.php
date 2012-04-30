<?php
    $m = $this->model;
    $formUrl = BApp::href('users/form/?id='.$m->id);
?>
<script>
head(function() {
    window.adminForm = FCom.Admin.form({
        form:     '#users-form',
        tabs:     '.adm-tabs-left li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo $formUrl ?>',
        url_post: '<?php echo $formUrl ?>'
    });
    $("#users-form").validate();
});
</script>
<form id="users-form" action="<?php echo $formUrl ?>" method="post">
    <header class="adm-page-title">
        <span class="title" id="tab-title">
            <?php echo $this->mode==='create' ? 'Create New User' : $this->q($m->username.' - '.$m->email) ?>
            <?php if ($m->status): ?><sup>(<?php echo $this->q($m->fieldOptions('status', $m->status)) ?>)</sup><?php endif ?>
        </span>
        <div class="btns-set">
            <button type="button" class="st2 sz2 btn" onclick="location.href='<?php echo BApp::href('users')?>'" type="button"><span>Back to list</span></button>
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