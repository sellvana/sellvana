<?php
    $attrSetsCtrl = FCom_CustomAttr_Admin_Controller_AttrSets::i();
?>
<header class="adm-page-title">
    <span class="title">Attribute Sets</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="location.href='<?php echo BApp::m('FCom_CustomAttr')->baseHref()?>/attrsets/form/'"><span>New Attribute Set</span></button>
    </div>
</header>
<div id="attrsets-layout">
    <div class="ui-layout-west">
        <?=$this->view('jqgrid')->set('config', $attrSetsCtrl->attrSetsGridConfig()) ?>
    </div>
    <div class="ui-layout-center">
        <?=$this->view('jqgrid')->set('config', $attrSetsCtrl->attributesGridConfig()) ?>
    </div>
</div>
<script>
head(function() {
    var linkedProductslayout = $('#attrsets-layout').height($('.adm-wrapper').height()).layout({
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
})
</script>
