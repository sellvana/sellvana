<?php
$loggedIn = FCom_Customer_Model_Customer::i()->isLoggedIn();
?>
<?php if (!$this->cart->items()): ?>
    	<p class="note-msg"><?= BLocale::_("There are no products matching the selection") ?>.</p>
<?php else: ?>
    	<div class="col-cart-left data-table">
	        <header class="page-title">
	            <h1 class="title"><?= BLocale::_("Shopping Cart") ?></h1>
	        </header>
	    	<form name="cart" action="<?=BApp::href('cart')?>" method="post">
		        <table>
		            <col width="30"/>
		            <col width="60"/>
		            <col/>
		            <col width="120"/>
		            <col width="80"/>
		            <col width="120"/>
		            <thead>
		                <tr>
		                	<th class="a-center">Remove</th>
		                    <th colspan="2" class="a-left"><?= BLocale::_("Product") ?></th>
		                    <th class="a-right"><?= BLocale::_("Price") ?></th>
		                    <th class="a-center"><?= BLocale::_("Qty") ?></th>
		                    <th class="a-right"><?= BLocale::_("Subtotal") ?></th>
		                </tr>
		            </thead>
		            <tbody>
		<?php foreach ($this->cart->items() as $item): $p = $item->product(); if (!$p) continue; ?>
		                <tr id="tr-product-<?=$p->id?>">
		                	<td class="a-center"><label><input type="checkbox" name="remove[]" class="remove-checkbox f-none" value="<?=$item->id?>"></label></td>
		                    <td>
		                        <img src="<?=$this->q($p->thumbUrl(80, 80))?>" width="80" height="80" class="product-image" alt="<?=$this->q($p->product_name)?>"/>
		                    </td>
		                    <td>
		                        <span class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></span>
		                    </td>
		                    <td class="a-right">
		                        <div class="price-box <?=$loggedIn?'logged-in':'logged-out'?>">
		                            <div class=""><span class="price">$<?=number_format($p->base_price)?></span></div>
		                        </div>
		                    </td>
		                    <td class="a-center">
		                        <input type="text" size="3" name="qty[<?=$item->id?>]" class="qty" value="<?=$item->qty*1?>"/>
		                    </td>
		                    <td class="a-right">
		                    	<div class="price-box">
		                        	<div class=""><span class="price"><?=number_format($item->rowTotal(), 2)?></span></div>
		                        </div>
		                    </td>
		                </tr>
		<?php endforeach ?>
		            </tbody>
		            <tfoot>
		                <td></td>
		                <td></td>
		                <td></td>
		                <td></td>
		                <td><button type="submit" class="button btn-aux"><span><?= BLocale::_("Update Cart") ?></span></button></td>
		                <td class="a-right">$<span class="cart-subtotal"><?=number_format($this->cart->subtotal)?></span></td>
		            </tfoot>
		        </table>
	    	</form>
	    	<section class="col2-set sub-cart">
		    	<form action="<?=BApp::href('cart')?>" method="post" class="col first">
		    		<header><?= BLocale::_("Promo Code") ?></header>
		    		<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the </p>
		    		<p><input type="text"/><button class="button btn-aux"><span><?= BLocale::_("Submit") ?></span></button></p>
		    	</form>
		    	<form action="<?=BApp::href('cart')?>" method="post" class="col last">
		    		<header><?= BLocale::_("Shipping Estimate") ?></header>
		    		<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the </p>
			            <?php if ($this->shipping_esitmate): ?>
			           <?php if ($this->shipping_esitmate) :?>
			                    <ul class="shipping-estimates">
			                        <?php foreach($this->shipping_esitmate as $estimate): ?>
			                            <li><?=$estimate['description']?> (<?=$estimate['estimate']?>)</li>
			                        <?php endforeach; ?>
			                    </ul>
			                    <?php endif; ?>
			            <?php endif; ?>
		            <p><strong><?= BLocale::_("Post code") ?></strong><br/>
		            	<input type="text" size="4" name="postcode" value="" style="width:150px;"/><button type="submit" class="button btn-aux"><span><?= BLocale::_("Submit") ?></span></button></p>
		    	</form>
	    	</section>
            <?=$this->hook('promotions') ?>
    	</div>
    	<div class="col-cart-right">
    		<div class="cart-totals">
	    		<table>
	    			<tr>
	    				<td>Subtotal</td>
	    				<td>$4499.97</td>
	    			</tr>
	    			<tr>
	    				<td>Shipping &amp; Handling</td>
	    				<td>$399.99</td>
	    			</tr>
	    			<tr class="grand-total">
	    				<td>Grand Total</td>
	    				<td>$4899.97</td>
	    			</tr>
	    		</table>
    		</div>
           	<ul class="checkout-btns">
           		<?php if ($this->redirectLogin) :?>
	                <li><a href="<?=BApp::href('checkout/login')?>" class="button btn-sz2"><span><?= BLocale::_("Proceed to Checkout") ?></span></a></li>
	            <?php else :?>
	                <li><a href="<?=BApp::href('checkout')?>" class="button btn-sz2"><span><?= BLocale::_("Proceed to Checkout") ?></span></a></li>
	            <?php endif; ?>
	       	</ul>
    	</div>
    	<div class="clearer"></div>
    	<br/><br/>
		<section class="block block-who-bought-also-bought">
			<header class="block-title"><strong class="title"><?= BLocale::_("Customers Who Bought Items in Your Shopping Cart Also Bought") ?></strong></header>
			<div class="block-content">
				<div class="product-listing style1">
					<ul>
						<li class="item">
							<a href="#" class="product-image"><img src="http://dev.unirgy.com/fulleron/FCom/Frontend/img/ph/prod_thumbnail.png" width="150" height="150" alt=""/></a>
							<a href="#" class="product-name">Toshiba 24L4200U 24-Inch 1080p 60Hz LED TV</a>
							<div class="rating">
								<span class="rating-out">
									<span class="rating-in" style="width:35%;"></span>
								</span>
							</div>
							<div class="price-box">
								<span class="regular-price">$1,149.99</span>
							</div>
						</li>
						<li class="item">
							<a href="#" class="product-image"><img src="http://dev.unirgy.com/fulleron/FCom/Frontend/img/ph/prod_thumbnail.png" width="150" height="150" alt=""/></a>
							<a href="#" class="product-name">Toshiba 24L4200U 24-Inch 1080p 60Hz LED TV</a>
							<div class="price-box">
								<span class="regular-price">$1,149.99</span>
							</div>
						</li>
						<li class="item">
							<a href="#" class="product-image"><img src="http://dev.unirgy.com/fulleron/FCom/Frontend/img/ph/prod_thumbnail.png" width="150" height="150" alt=""/></a>
							<a href="#" class="product-name">Toshiba 24L4200U 24-Inch 1080p 60Hz LED TV</a>
							<div class="price-box">
								<span class="regular-price">$1,149.99</span>
							</div>
						</li>
						<li class="item">
							<a href="#" class="product-image"><img src="http://dev.unirgy.com/fulleron/FCom/Frontend/img/ph/prod_thumbnail.png" width="150" height="150" alt=""/></a>
							<a href="#" class="product-name">Toshiba 24L4200U 24-Inch 1080p 60Hz LED TV</a>
							<div class="price-box">
								<span class="regular-price">$1,149.99</span>
							</div>
						</li>
						<li class="item">
							<a href="#" class="product-image"><img src="http://dev.unirgy.com/fulleron/FCom/Frontend/img/ph/prod_thumbnail.png" width="150" height="150" alt=""/></a>
							<a href="#" class="product-name">Toshiba 24L4200U 24-Inch 1080p 60Hz LED TV</a>
							<div class="price-box">
								<span class="regular-price">$1,149.99</span>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</section>
<?php endif ?>
<script>
$('.vendor-count').tooltip({effect:'slide'});
</script>
