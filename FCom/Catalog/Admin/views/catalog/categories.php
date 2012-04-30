<?php $formUrl = BApp::href('catalog/categories/tree_form/') ?>
<header class="adm-page-title">
    <span class="title">Categories</span>
    <div class="btns-set">
    </div>
</header>
<div id="categories-layout" class="adm-content-box">
    <div class="ui-layout-west adm-sidebar">
        <div class="ui-widget-header">
        	Categories
            <input type="checkbox" id="categories-tree-lock"/>
            <input type="checkbox" id="categories-expand-collapse"/>
        </div>
        <div id="categories"></div>
    </div>

<form id="categories-tree-form" action="<?php echo $formUrl ?>" method="post">
    <div class="ui-layout-center adm-main" id="categories-form-container">
    </div>
</form>
</div>
<script>
head(function() {
    FCom.Admin.checkboxButton('#categories-tree-lock', {def:true, off:{icon:'unlocked', label:'Unlocked'}, on:{icon:'locked', label:'Locked'}});
    FCom.Admin.checkboxButton('#categories-expand-collapse', {
        off:{icon:'triangle-1-e', label:'Expand All'}, on:{icon:'triangle-1-s', label:'Collapse All'},
        click:function(ev) { $('#categories').jstree(this.checked?'open_all':'close_all', $('#1>ul>li')); }
        //TODO: fetch ancestors only for root node
    });

    FCom.Admin.tree('#categories', {
        url:'<?=BApp::href('catalog/categories/tree_data')?>'
        , on_dblclick: function (n) { loadForm(n.attr('id')); }
        , on_select: function (n) { loadForm(n.attr('id')); }
        , lock_flag: '#categories-tree-lock'
    });

    var cmsNavLayout = $('#categories-layout').height($('.adm-wrapper').height()).layout({
        useStateCookie: true,
        west__minWidth:400,
        west__spacing_open:1,
        west__closable:false,
        triggerEventsOnLoad: true,
        onresize:function(pane, $Pane, paneState) {
            $('.ui-jqgrid-btable:visible', $Pane).each(function(index) {
                if (!this.id.match(/_t$/)) {
                    $(this).setGridWidth(paneState.innerWidth - 20);
                }
            });
        }
    });

    function loadForm(id) {
        var url = '<?php echo $formUrl ?>?id='+id;
        $('#categories-form-container').load(url, function() {
            $('#categories-tree-form').attr('action', url);
            window.adminForm = FCom.Admin.form({
                tabs:     '.adm-tabs li',
                panes:    '.adm-tabs-content',
                url_get:  url,
                url_post: url
            });
        });
    }

})
</script>