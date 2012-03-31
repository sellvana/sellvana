<?php
$tabs = $this->sortedTabs();
$m = $this->model;
?>
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
            <section id="tab-main" class="adm-tabs-content" data-loaded="true">
                <form id="nav-tree-form" action="<?php echo BApp::href('cms/nav_tree_form/'.$m->id) ?>" method="post">
                    <fieldset>
                        <ul>
                            <li><label for="node_type">Node Type</label><select id="node_type" name="node[node_type]">
                            <?php echo $this->optionsHtml(array('cms_page'=>'CMS Page', 'catalog_category'=>'Category')) ?>
                            </select></li>
                        </ul>
                    </fieldset>
                </form>
            </section>
<?php foreach ($tabs as $k=>$tab): if (!empty($tab['view'])): ?>
            <section id="tab-<?php echo $this->q($k) ?>" class="adm-tabs-content"
                <?php if ($k!==$this->cur_tab): ?>hidden<?php endif ?>
                <?php if (empty($tab['async'])): ?>data-loaded="true"<?php endif ?>
            >
<?php if (empty($tab['async'])) echo $this->view($tab['view']) ?>
            </section>
<?php endif; endforeach ?>
        </div>
    </div>
</section>
<script>
head(function() {
    window.adminForm = Admin.form({
        tabs:     '.adm-tabs-left li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo BApp::href('cms/nav_tree_form_tab/'.$m->id) ?>',
        url_post: '<?php echo BApp::href('cms/nav_tree_form/'.$m->id) ?>'
    });
})
</script>