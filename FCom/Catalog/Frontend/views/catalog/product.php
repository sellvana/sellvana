<?php
$prod = $this->product;
$cat = $this->category;
?>

<div class="main col1-layout">
    <div class="col-main">
        <div id="messages_product_view"></div>
        <div class="product-view">
            <div class="product-essential">
                <form action="" method="post" onsubmit="return false;">
                    <input type="hidden" name="id" value="<?=$prod->id?>">
                    <div class="product-shop">
                        <div class="add-to-cart">

                            <?=$this->view('cart/add2cart', array('prod' => $prod))?>

                            <?=$this->view('wishlist/add2wishlist', array('prod' => $prod))?>

                            <label class="compare-label"><input type="checkbox" name="compare" class="compare-checkbox" value="<?=$prod->id?>"> <?= BLocale::_("Compare") ?></label>

                            <?=$this->view('compare/block')?>

                        </div>
                        <div class="product-name">
                            <h1><?=$this->q($prod->product_name)?></h1>
                        </div>
                        <div class="price-box">
                            <span class="price">$<?=number_format($prod->base_price, 2)?></span>
                        </div>
                        <p class="no-rating"><a href=""><?= BLocale::_("Be the first to review this product") ?></a></p>
                        <div class="short-description">
                            <?=$this->q($prod->description)?>
                        </div>
                        <div>
                            <?=$this->view('customfields/product', array('prod' => $prod))?>
                        </div>
                    </div>

                    <?php
                    $mediaList = FCom_Catalog_Model_ProductMedia::i()->orm()->where('product_id', $prod->id())->where('media_type', 'I')->find_many();
                    ?>
                    <div class="product-img-box">
                        <p class="product-img">
                            <img src="<?=$prod->thumbUrl(50, 50)?>" alt="<?=$this->q($prod->product_name)?>" title="<?=$this->q($prod->product_name)?>"></p>
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

            <div class="product-collateral" id="tabs">
            	<ul class="tabs">
            		<li><a href="#manufacturer"><?= BLocale::_("Manufacturer Web Page") ?></a></li>
            		<li><a href="#overview"><?= BLocale::_("Overview") ?></a></li>
            		<li><a href="#specifications"><?= BLocale::_("Specifications") ?></a></li>
            		<li><a href="#reviews"><?= BLocale::_("Reviews") ?></a></li>
            		<li><a href="#similar"><?= BLocale::_("Similar Products") ?></a></li>
            		<li><a href="#family"><?= BLocale::_("Family Products") ?></a></li>
            		<li><a href="#accessories"><?= BLocale::_("Accessories") ?></a></li>
            	</ul>
                <div class="panes">
                    <div class="tab-content box-description">
                        <h4><?= BLocale::_("Overview") ?></h4>
                            Tray Acrylic Material

                            1 lb package contains: 1 lb powder, 8oz liquid.

                            3 lb package contains: 3 lbs powder, 16oz liquid.
                    </div>
                    <div class="tab-content box-tags">
                        <h4><?= BLocale::_("Specifications") ?></h4>
                    </div>
                    <div class="tab-content">
                        <h4><?= BLocale::_("Reviews") ?></h4>
                        <a href="<?=Bapp::href('prodreviews/add')?>?pid=<?=$prod->id?>"><?= BLocale::_("Add review") ?></a><br/><br/>
                        <?php if ($this->product_reviews) :?>
                            <?php foreach ($this->product_reviews as $review) :?>
                            <div style="border:1 px solid black;">
                                <input name="review[rating<?=$review->id?>]" type="radio" class="star" disabled="disabled" value="1" <?=$review->rating == 1 ? 'checked': ''?>/>
                                <input name="review[rating<?=$review->id?>]" type="radio" class="star" disabled="disabled" value="2" <?=$review->rating == 2 ? 'checked': ''?> />
                                <input name="review[rating<?=$review->id?>]" type="radio" class="star" disabled="disabled" value="3" <?=$review->rating == 3 ? 'checked': ''?>/>
                                <input name="review[rating<?=$review->id?>]" type="radio" class="star" disabled="disabled" value="4" <?=$review->rating == 4 ? 'checked': ''?>/>
                                <input name="review[rating<?=$review->id?>]" type="radio" class="star" disabled="disabled" value="5" <?=$review->rating == 5 ? 'checked': ''?>/>
                                <span style="font-weight: bold; padding-left: 15px;"><?=$review->title?></span>
                                <?=date("F d, Y", strtotime($review->created_at))?>
    <br/>
                                <?=nl2br($review->text)?><br/>
                                <div id="block_review_helpful_<?=$review->id?>">
                                    <form action="<?=Bapp::href('prodreviews/helpful')?>" method="post"  onsubmit="return false;">
                                    <input type="hidden" name="pid" value="<?=$prod->id?>">
                                    <input type="hidden" name="rid" value="<?=$review->id?>">
                                    <?= BLocale::_("Was this review helpful to you") ?>?
                                    <button type="submit" name="review_helpful" value="yes"
                                            onclick="add_review_rating('<?=Bapp::href('prodreviews/helpful')?>', <?=$review->id?>, 'yes');"><?= BLocale::_("Yes") ?></button>
                                    <button type="submit" name="review_helpful" value="no"
                                            onclick="add_review_rating('<?=Bapp::href('prodreviews/helpful')?>', <?=$review->id?>, 'no');"><?= BLocale::_("No") ?></button>
                                    </form>
                                </div>
                                <span id="block_review_helpful_done_<?=$review->id?>" style="color:green"></span>
                                <br/><br/>
                            </div>
                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>
                    <div class="tab-content">
                    	<h4><?= BLocale::_("Similar Products") ?></h4>
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
                <div class="tab-content">
                    	<h4><?= BLocale::_("Related Products") ?></h4>
                    	<table class="product-list">
				        	<colgroup><col width="30">
				        	<col width="60">
				        	<col>
				        	<col width="180">
				            </colgroup><tbody>
<?=$this->view('catalog/product/rows')->set('products', FCom_Catalog_Model_ProductLink::i()->productsByType($prod->id, 'related')) ?>
				            </tbody>
				        </table>
        	</div>
                    <div class="tab-content">
                        <h4><?= BLocale::_("Family Products") ?></h4>
                        <table class="product-list">
                            <colgroup><col width="30">
                            <col width="60">
                            <col>
                            <col width="180">
                            </colgroup><tbody>
<?=$this->view('catalog/product/rows')->set('products', FCom_Catalog_Model_Product::i()->orm()->offset(20)->limit(10)->find_many()) ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-content">
                        <h4><?= BLocale::_("Accessories") ?></h4>
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
                </div>
            </div>
        </div>
    </div>
</div>