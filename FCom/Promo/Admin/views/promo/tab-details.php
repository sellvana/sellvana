<?php
$m = $this->model;
$promoCtrl = FCom_Promo_Admin_Controller::i();
?>
<div id="details-layout">
    <div class="ui-layout-west">
        <input type="hidden" name="_del_group_ids" value=""/>
        <h2>BUY
            <input type="text" name="model[buy_amount]" value="<?=$m->buy_amount?>" style="width:50px"/>
            <?=$m->buy_type ? $m->fieldOptions('buy_type', $m->buy_type) : ''?>
        </h2>

        <div id="group-container-buy" class="group-container">
<?php foreach ($m->groups() as $g): if ($g->group_type!=='buy') continue; ?>
            <?=$this->view('jqgrid')->set('config', $promoCtrl->productGridConfig($m, $g->group_type, $g->id)) ?>
<?php endforeach; ?>
        </div>
<?php if ($m->buy_group!=='one'): ?>
        <button type="button" class="st1 sz2 btn" id="add-group-buy">Add BUY Group</button>
        <br><br>
<?php endif ?>

        <h2>GET
            <input type="text" name="model[get_amount]" value="<?=$m->get_amount?>" style="width:50px"/>
            <?=$m->get_type ? $m->fieldOptions('get_type', $m->get_type) : ''?>
        </h2>
<?php if ($m->get_group==='diff_group'): ?>
        <div id="group-container-get" class="group-container">
<?php foreach ($m->groups() as $g): if ($g->group_type!=='get') continue; ?>
            <?=$this->view('jqgrid')->set('config', $promoCtrl->productGridConfig($m, $g->group_type, $g->id)) ?>
<?php endforeach ?>
        </div>
        <button type="button" class="st1 sz2 btn" id="add-group-get">Add GET Group</button>
<?php endif ?>

    </div>
    <div class="ui-layout-center">
        <div class="group-container">
            <?=$this->view('jqgrid')->set('config', FCom_Catalog_Admin_Controller_Products::i()->productLibraryGridConfig('productLibrary')) ?>
        </div>
    </div>
</div>

<script>
require(['jquery', 'fcom.admin'], function($) {
    $(function() {
        var layout = $('#details-layout').height($('.adm-wrapper').height()).layout({
            useStateCookie: true,
            west__minWidth:400,
            west__spacing_open:20,
            west__closable:false,
            triggerEventsOnLoad: true,
            onresize:function(pane, $Pane, paneState) {
                $('.ui-jqgrid-btable:visible', $Pane).each(function(index) {
                    $(this).setGridWidth(paneState.innerWidth - 20);
                });
            }
        });
        $(window).resize(function(ev) { $('#details-layout').height($('.adm-wrapper').height()); });

        $('#details-layout .ui-layout-west .ui-jqgrid-btable').each(function(idx, el) {
            new FCom.Admin.TargetGrid({source:'#productLibrary', target:el});
        });

        (function() {
            var newId = 0, baseUrl = '<?=BApp::href('promo/form/'.$m->id.'/group') ?>';
            function addGroup(type) {
                var url = baseUrl+'?type='+type+'&group_id='+(--newId);
                $.get(url, function(data, status, xhr) {
                    $('#group-container-'+type).append(data);
                    layout.resizeAll();
                });
                return false;
            }
            $('#add-group-buy').click(function(ev) { return addGroup('buy'); });
            $('#add-group-get').click(function(ev) { return addGroup('get'); });
        })();

        function removeGroup(el) {
            var grid = $(el).parents('.ui-jqgrid');
            var gId = $('.ui-jqgrid-title input[name=_group_id]', grid).val();
            var deleteIds = $('input[name=_del_group_ids]');
            if (gId>0) deleteIds.val(deleteIds.val()+','+gId);
            grid.remove();
        }
    })
})
</script>
