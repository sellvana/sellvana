<?php $formUrl = BApp::href('cms/nav/tree_form/') ?>
<header class="adm-page-title">
    <span class="title">CMS Navigation</span>
    <div class="btns-set">
    </div>
</header>
<div id="cms-nav-layout">
    <div class="ui-layout-west">
        <div class="ui-widget-header">
            <input type="checkbox" id="nav-tree-lock"/>
            <input type="checkbox" id="nav-expand-collapse"/>
        </div>
        <div id="cms_nav"></div>
    </div>

<form id="nav-tree-form" action="<?php echo $formUrl ?>" method="post">
    <div class="ui-layout-center" id="nav-form-container">
    </div>
</form>
</div>
<script>
head(function() {
    Admin.checkboxButton('#nav-tree-lock', {def:true, off:{icon:'unlocked', label:'Unlocked'}, on:{icon:'locked', label:'Locked'}});
    Admin.checkboxButton('#nav-expand-collapse', {
        off:{icon:'triangle-1-e', label:'Expand All'}, on:{icon:'triangle-1-s', label:'Collapse All'},
        click:function(ev) { $('#cms_nav').jstree(this.checked?'open_all':'close_all', $('#1>ul>li')); }
        //TODO: fetch ancestors only for root node
    });

    Admin.tree('#cms_nav', {
        url:'<?=BApp::href('cms/nav/tree_data')?>'
        , on_dblclick: function (n) { loadForm(n.attr('id')); }
        , on_select: function (n) { loadForm(n.attr('id')); }
        , lock_flag: '#nav-tree-lock'
    });

    var cmsNavLayout = $('#cms-nav-layout').height($('.adm-wrapper').height()).layout({
        useStateCookie: true,
        west__minWidth:400,
        west__spacing_open:20,
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
        var url = '<?php echo $formUrl ?>'+id;
        $('#nav-form-container').load(url, function() {
            $('#nav-tree-form').attr('action', url);
            window.adminForm = Admin.form({
                tabs:     '.adm-tabs-left li',
                panes:    '.adm-tabs-content',
                url_get:  url,
                url_post: url
            });
        });
    }
})
</script>