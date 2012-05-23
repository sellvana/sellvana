<?php
/**
* @param string $this->form_id
* @param string $this->form_url
* @param string $this->title
* @param array $this->actions
* @param string $this->cur_tab
*/
$tabs = $this->sortedTabs();
?>
<script>
head(function() {
    window.adminForm = FCom.Admin.form({
        form:     '#<?=$this->form_id?>',
        tabs:     '.adm-tabs-sidebar li',
        panes:    '.adm-tabs-content',
        url_get:  '<?=$this->form_url?>',
        url_post: '<?=$this->form_url?>'
    });
    $("#<?=$this->form_id?>").validate();
});
</script>
<form id="<?=$this->form_id?>" action="<?=$this->form_url?>" method="post">
    <header class="adm-page-title">
        <span class="title" id="tab-title">
            <?=$this->q($this->title)?>
        </span>
        <div class="btns-set">
            <?=join(' ', (array)$this->actions)?>
        </div>
    </header>
    <?=$this->messagesHtml('admin')?>
    <section class="adm-content-box info-view-mode">
        <aside class="<?=$this->sidebar_img ? 'form-img-sidebar' : 'adm-sidebar'?>">
            <?php if ($this->sidebar_img): ?>
                <img src="<?=$this->q($this->sidebar_img)?>" width="98" height="98"/>
            <?php endif ?>
            <nav class="adm-tabs-sidebar">
                <ul>
<?php foreach ($tabs as $k=>$tab): if (!empty($tab['disabled'])) continue; ?>
                    <li <?php if ($k===$this->cur_tab): ?>class="active"<?php endif ?>>
                        <a href="#tab-<?php echo $this->q($k) ?>"><span class="icon"></span><?php echo $this->q($tab['label']) ?></a>
                    </li>
<?php endforeach ?>
                </ul>
            </nav>
        </aside>
        <div class="adm-main">
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