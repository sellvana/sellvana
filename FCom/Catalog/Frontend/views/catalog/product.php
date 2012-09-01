<?php
$prod = $this->product;
$cat = $this->category;
?>
        <div class="product-view">
            <div class="product-main">
                <form action="" method="post" onsubmit="return false;">
                    <input type="hidden" name="id" value="<?=$prod->id?>">
                    <div class="col-product-shop">
                        <h1 class="product-name"><?=$this->q($prod->product_name)?> Toshiba 24L4200U 24-Inch 1080p 60Hz LED TV</h1>
                        <!--<p class="no-rating"><a href="<?=Bapp::href('prodreviews/add')?>?pid=<?=$prod->id?>"><?= BLocale::_("Be the first to review this product") ?></a></p>-->
				        <span class="rating">
				            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
				            3.5 of 5 (<a href="#">16 reviews</a>)
				        </span>
				        <div class="price-box">
				        	<div class="old-price"><span class="title">Was:</span><span class="price">$399.99</span></div>
				        	<div class="new-price"><span class="title">Now:</span><span class="price">$399.99</span></div>
				        </div>
                        <!--<div class="price-box">
                            <span class="price">$<?=number_format($prod->base_price, 2)?></span>
                        </div>-->
                        <div class="add-to-cart-line">
                            <?=$this->view('cart/add2cart', array('prod' => $prod))?>
                            <?=$this->view('wishlist/add2wishlist', array('prod' => $prod))?>
                            <label for="compare-<?=$prod->id?>" class="checkbox-line"><input type="checkbox" name="compare" id="compare-<?=$prod->id?>" value="<?=$prod->id?>"> <?= BLocale::_("Compare") ?></label>
                            <label for="wishlist-<?=$prod->id?>" class="checkbox-line"><input type="checkbox" name="wishlist" id="wishlist-<?=$prod->id?>" value="<?=$prod->id?>"> <?= BLocale::_("Wishlist") ?></label>
                            <div class="clearer"></div>
                        </div>
                        <div class="short-description">
                            <?=$this->s($prod->description)?>
                            <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting.</p>
                        </div>
                        <div>
                            <?=$this->view('customfields/product', array('prod' => $prod))?>
                        </div>
                        <?=$this->view('compare/block')?>
                    </div>

                    <?php
                    $mediaList = FCom_Catalog_Model_ProductMedia::i()->orm()->where('product_id', $prod->id())->where('media_type', 'I')->find_many();
                    ?>
                    <div class="col-product-image">
                        <div class="product-image">
                        	<img src="<?=$prod->thumbUrl(400, 400)?>" alt="<?=$this->q($prod->product_name)?>" title="<?=$this->q($prod->product_name)?>" width="400" height="400"/></div>
                        <div class="additional-views">
                            <ul>
                                <?php foreach($mediaList as $media):?>
                                <li>
                                    <a href="<?=$media->getUrl()?>" rel="lightbox[prod_<?=$prod->id?>]" title=""><img src="<?=$media->getUrl()?>" width="40" height="40" alt=""></a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="clearer"></div>
                </form>
            </div>
            <div class="product-more-info">
            	<ul class="tabs">
            		<li><a href="#overview"><?= BLocale::_("Overview") ?></a></li>
            		<li><a href="#specifications"><?= BLocale::_("Specifications") ?></a></li>
            		<li><a href="#similar"><?= BLocale::_("Similar Products") ?></a></li>
            		<li><a href="#accessories"><?= BLocale::_("Accessories") ?></a></li>
            		<li><a href="#reviews"><?= BLocale::_("Reviews") ?></a></li>
            	</ul>
                <div class="panes">
                    <div class="tab-content tab-overview">
                        <header><?= BLocale::_("Overview") ?></header>
                            Tray Acrylic Material

                            1 lb package contains: 1 lb powder, 8oz liquid.

                            3 lb package contains: 3 lbs powder, 16oz liquid.
                    </div>
                    <div class="tab-content tab-specifications">
                        <header><?= BLocale::_("Specifications") ?></header>
                    </div>
                    <div class="tab-content tab-similar-products">
                    	<header><?= BLocale::_("Similar Products") ?></header>
                    	<table class="product-list">
				        	<colgroup><col width="30">
				        	<col width="60">
				        	<col>
				        	<col width="180">
				            </colgroup><tbody>
<?=$this->view('catalog/product/rows')->set('products', FCom_Catalog_Model_ProductLink::i()->productsByType($prod->id, 'similar')) ?>
				            </tbody>
				        </table>
				  	</div>
	                <div class="tab-content tab-accessories">
	                    <header><?= BLocale::_("Accessories") ?></header>
	                    <table class="product-list">
	                        <colgroup><col width="30">
	                        <col width="60">
	                        <col>
	                        <col width="180">
	                        </colgroup><tbody>
	<?=$this->view('catalog/product/rows')->set('products', FCom_Catalog_Model_Product::i()->orm()->offset(30)->limit(10)->find_many()) ?>
	                        </tbody>
	                    </table>
	                </div>
                    <div class="tab-content tab-reviews">
                    	<header><?= BLocale::_("Reviews") ?></header>
                        <?php echo $this->hook('prodreviews-reviews', array('product' => $prod)) ?>
                        <?//=$this->view('prodreviews/reviews', array('prod' => $prod, 'reviews' => $this->product_reviews))?>
                    </div>
            	</div>
<script>
$(function() {
    // setup ul.tabs to work as tabs for each div directly under div.panes
    $(".tabs").tabs("div.panes > div").data('tabs');
});
</script>
        	</div>
        </div>