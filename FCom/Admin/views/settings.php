<?php
$formUrl = BApp::href('settings');
?>
<script>
head(function() {
    function initTabs() {
        $('.settings-container', this).accordion({
            header: '> div > h3'
        }).sortable({
            axis: 'y',
            handle: 'h3',
            stop: function(event, ui) { ui.item.children('h3').triggerHandler('focusout') }
        });
    }
    window.adminForm = Admin.form({
        form:     '#settings-form',
        tabs:     '.adm-tabs-left li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo $formUrl ?>',
        url_post: '<?php echo $formUrl ?>',
        on_tab_load: initTabs
    });
    $('#settings-form').validationEngine();
    initTabs.apply($('#settings-form'));
    $('.adm-tabs-left > ul').sortable({
        axis: 'y',
        stop: function(event, ui) { ui.item.triggerHandler('focusout') }
    });
});
</script>
<form id="settings-form" action="<?php echo $formUrl ?>" method="post">
    <header class="adm-page-title">
        <span class="title">Settings</span>
        <div class="btns-set">
            <button type="submit" class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>Save All</span></button>
        </div>
    </header>
    <?php echo $this->messagesHtml() ?>
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