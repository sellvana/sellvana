<?php
$formUrl = BApp::href('settings');
?>
<script>
head(function() {
    function onSortableStop(event, ui) {
        var type = ui.item[0].nodeName.toLowerCase()=='li' ? 'tabs' : 'sections';
console.log(ui.item[0].id);
        var handle = ui.item;
        if (type=='tabs') handle = handle.children('h3');
        handle.triggerHandler('focusout');

        return; //TODO: figure out whether to personalize settings items order

        var url = '<?php echo BApp::href('my_account/personalize') ?>';

        switch (type) {
        case 'tabs':
            var items = [];
            ui.item.parent().children().each(function(idx, el) {
                items.push(el.id.replace(/^settings-tab-/, ''));
            });
            //$.post(url, {'do':'settings.'+type+'.order', items:items});
            break;

        case 'sections':
            var items = [];
            break;
        }
    }

    function initTabs() {
        $('.settings-container', this).accordion({header: '> div > h3'})
            .sortable({axis: 'y', handle: 'h3', stop: onSortableStop, distance:5});
    }
    window.adminForm = FCom.Admin.form({
        form:     '#settings-form',
        tabs:     '.adm-tabs-sidebar li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo $formUrl ?>',
        url_post: '<?php echo $formUrl ?>',
        on_tab_load: initTabs
    });
    $('#settings-form').validate();
    initTabs.apply($('#settings-form'));
    $('.adm-tabs-sidebar > ul').sortable({axis: 'y', stop: onSortableStop, distance:5});
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
    	<aside class="form-img-sidebar">
	        <nav class="adm-tabs-sidebar">
	            <ul>
	<?php foreach ($this->tabs as $k=>$tab): if (!empty($tab['disabled'])) continue; ?>
	                <li id="settings-tab-<?php echo $this->q($k) ?>" <?php if ($k===$this->cur_tab): ?>class="active"<?php endif ?>>
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