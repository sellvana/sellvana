<div class="block block-compare">
    <header class="block-title"><strong>Compare</strong>up to 4 items</header>
    <div class="block-content">
        <ul>
        	<li></li>
        	<li></li>
        	<li></li>
        	<li></li>
        </ul>
        <a href="<?php echo BApp::href('catalog/compare')?>" class="button btn-aux" id="compare-trigger" rel="#compare-overlay"><span><?= BLocale::_("Compare") ?></span></a>
        <a href="#" class="reset-btn">Reset</a>
    </div>
</div>
<div class="overlay" id="compare-overlay"><div class="contentWrap"></div></div>
<script>
$(function() { // let all checkboxes to load first
    $('.block-compare').data('compare', new FCom.CompareBlock({
        thumbContainer: '.block-compare',
        prodContainerPrefix: '#tr-product-', 
        checkbox: '.compare-checkbox', 
        img: '.product-image'
    }));

    $("#compare-trigger").overlay({
        mask: { color:'#000', loadSpeed:0, opacity:0.3 }, 
        effect:'default', 
        speed:0, 
        onBeforeLoad: function() {
            this.getOverlay().find(".contentWrap").load(this.getTrigger().attr("href"));
        }
    });
});
</script>
