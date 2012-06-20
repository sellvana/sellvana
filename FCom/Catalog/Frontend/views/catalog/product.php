<?php
$prod = $this->product;
$cat = $this->category;
?>

<div class="main col1-layout">
    <?=$this->view('breadcrumbs')?>
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

                            <label class="compare-label"><input type="checkbox" name="compare" class="compare-checkbox" value="<?=$prod->id?>"> Compare</label>

                            <?=$this->view('compare/block')?>

                        </div>
                        <div class="product-name">
                            <h1><?=$this->q($prod->product_name)?></h1>
                        </div>
                        <div class="price-box">
                            <span class="price">$<?=number_format($prod->base_price, 2)?></span>
                        </div>
                        <p class="no-rating"><a href="">Be the first to review this product</a></p>
                        <div class="short-description">
                            <?=$this->q($prod->description)?>
                        </div>
                        <div>
                            <?=$this->view('customfields/product', array('prod' => $prod))?>
                        </div>
                    </div>

                    <div class="product-img-box">
                        <p class="product-img">
                            <img src="<?=$prod->thumbUrl(50, 50)?>" alt="<?=$this->q($prod->product_name)?>" title="<?=$this->q($prod->product_name)?>"></p>
                        <div class="additional-views">
                            <ul>
                                <li>
                                    <a href="<?=$prod->imageUrl(true)?>" title=""><img src="<?=$prod->thumbUrl(40, 40)?>" width="40" height="40" alt=""></a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="clearer"></div>
                </form>
            </div>

            <div class="product-collateral" id="tabs">
            	<ul class="tabs">
            		<li><a href="#manufacturer">Manufacturer Web Page</a></li>
            		<li><a href="#overview">Overview</a></li>
            		<li><a href="#specifications">Specifications</a></li>
            		<li><a href="#reviews">Reviews</a></li>
            		<li><a href="#similar">Similar Products</a></li>
            		<li><a href="#family">Family Products</a></li>
            		<li><a href="#accessories">Accessories</a></li>
            	</ul>
                <div class="panes">
                    <div class="tab-content box-description">
                        <h4>Overview</h4>
                            Tray Acrylic Material

                            1 lb package contains: 1 lb powder, 8oz liquid.

                            3 lb package contains: 3 lbs powder, 16oz liquid.
                    </div>
                    <div class="tab-content box-tags">
                        <h4>Specifications</h4>
                    </div>
                    <div class="tab-content">Reviews</div>
                    <div class="tab-content">
                    	<h4>Similar Products</h4>
                    	<table class="product-list">
				        	<colgroup><col width="30">
				        	<col width="60">
				        	<col>
				        	<col width="180">
				            </colgroup><tbody>
<?=$this->view('catalog/product/rows')->set('products', FCom_Catalog_Model_ProductLink::i()->products($prod->id, 'similar')) ?>
				            </tbody>
				        </table>
        	</div>
                <div class="tab-content">
                    	<h4>Related Products</h4>
                    	<table class="product-list">
				        	<colgroup><col width="30">
				        	<col width="60">
				        	<col>
				        	<col width="180">
				            </colgroup><tbody>
<?=$this->view('catalog/product/rows')->set('products', FCom_Catalog_Model_ProductLink::i()->products($prod->id, 'related')) ?>
				            </tbody>
				        </table>
        	</div>
                    <div class="tab-content">
                        <h4>Family Products</h4>
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
                        <h4>Accessories</h4>
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