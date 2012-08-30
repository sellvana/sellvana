<?php
$cart = FCom_Checkout_Model_Cart::sessionCart();
$itemPrice = round($cart->subtotal,2);
$itemNum = ceil($cart->item_num);
?>
<span class="mini-cart-wrapper<?php echo !$itemNum?' empty-cart':'';?>">
	<a href="<?=BApp::href('cart')?>" class="title"><?= BLocale::_("Cart") ?>: <span class="count" id="cart-num-items"><?=$itemNum?></span><em class="icon icon-left"></em><em class="icon icon-right"></em></a>
</span>
<!--<?//= BLocale::_("Total") ?>: $<span id="cart-subtotal"><?=$itemPrice?></span></a></li>-->