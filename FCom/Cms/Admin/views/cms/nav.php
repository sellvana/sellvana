<header class="adm-page-title">
    <span class="title">CMS Navigation</span>
    <div class="btns-set">
    </div>
</header>
<div id="cms-nav-layout">
    <div class="ui-layout-west">
        <div id="cms_nav"></div>
    </div>
    <div class="ui-layout-center" id="nav-form-container">
    </div>
</div>
<script>
head(function() {
    Admin.tree('#cms_nav', {
        url:'<?=BApp::href('cms/nav/tree_data')?>',
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

    $('#nav-form-container').load('<?php echo BApp::href('cms/nav/tree_form/') ?>');
})
</script>