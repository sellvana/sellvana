<?php
    $m = $this->model;
    $tabs = $this->sortedTabs();
?>
<script>
$(function() {
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
        <span class="title" id="tab-title"><?=$this->q($this->title)?></span>
        <div class="btns-set"><?=join(' ', (array)$this->actions)?></div>
    </header>
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

            <?php if ($m->id):  ?>
        <section class="adm-product-summary adm-section-group">
            <h2><?=$this->q($m->description)?><sup>(<?php echo $this->q($m->fieldOptions('status', $m->status)) ?>)</sup></h2>
            <table class="adm-form-subtable">
                <tr>
                    <td>BUY</td>
                    <td><strong><?php echo $this->q($m->fieldOptions('buy_type', $m->buy_type)) ?></strong></td>
                    <td>FROM</td>
                    <td><strong><?php echo $this->q($m->fieldOptions('buy_group', $m->buy_group)) ?></strong></td>
                    <td>GET</td>
                    <td><strong><?php echo $this->q($m->fieldOptions('get_type', $m->get_type)) ?></strong></td>
                    <td>OF</td>
                    <td><strong><?php echo $this->q($m->fieldOptions('get_group', $m->get_group)) ?></strong></td>
                </tr>
            </table>
        </section>
    <?php  endif ?>

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