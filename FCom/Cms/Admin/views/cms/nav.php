<?php $formUrl = BApp::href('cms/nav/tree_form/') ?>
<header class="adm-page-title">
    <span class="title">CMS Navigation</span>
    <div class="btns-set">
    </div>
</header>
<div id="cms-nav-layout">
    <div class="ui-layout-west">
        <div id="cms_nav"></div>
    </div>

<form id="nav-tree-form" action="<?php echo $formUrl ?>" method="post">
    <div class="ui-layout-center" id="nav-form-container">
    </div>
</form>
</div>
<script>
head(function() {
    Admin.tree('#cms_nav', {
        url:'<?=BApp::href('cms/nav/tree_data')?>',
        on_dblclick: function (n) { loadForm(n.attr('id')); },
        on_select: function (n) { loadForm(n.attr('id')); }
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
        $('#nav-form-container').load(url);
        $('#nav-tree-form').attr('action', url);
        adminForm.setOptions({url_get:url, url_post:url});
    }

    window.adminForm = Admin.form({
        tabs:     '.adm-tabs-left li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo $formUrl ?>',
        url_post: '<?php echo $formUrl ?>'
    });
})
</script>