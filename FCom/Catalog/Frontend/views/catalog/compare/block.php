<div class="block-compare">
    <div class="block-title"><strong>Compare</strong>up to 4 items</div>
    <div class="block-content">
        <ul><li></li><li></li><li></li><li></li></ul>
        <a href="<?php echo BApp::href('catalog/compare')?>" class="button" rel="#compare-overlay"><?= BLocale::_("Compare"); ?></a>
        <a href="#" class="reset-btn"><?= BLocale::_("Reset"); ?></a>
    </div>
</div>
<div class="overlay" id="compare-overlay"><div class="contentWrap"></div></div>
<script>
$(function() { // let all checkboxes to load first
    $('.block-compare').data('compare', new DentevaCompare({thumbContainer:'.block-compare', prodContainerPrefix:'#tr-product-', checkbox:'.compare-checkbox', img:'.product-img'}));
});
$("a[rel]").overlay({mask:{color:'#000',loadSpeed:0,opacity:0.3}, effect:'default', speed:0, onBeforeLoad: function() {
    this.getOverlay().find(".contentWrap").load(this.getTrigger().attr("href"));
}});
</script>
<style>
.overlay .close { display:block; width:20px; height:20px; background:red; }
#compare-overlay { display:none; width:940px; height:700px; background:#fff; border:solid 2px #000; }
</style>
