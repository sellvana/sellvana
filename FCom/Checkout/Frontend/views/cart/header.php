<?php
$cart = FCom_Checkout_Model_Cart::sessionCart();
$itemPrice = round($cart->subtotal,2);
$itemNum = ceil($cart->item_num);
?>
<li class="header-sup-cart"><a href="<?=BApp::href('cart')?>"><?= BLocale::_("Your Cart"); ?> <span class="count" id="cart-num-items">(<?=$itemNum?>)</span>
| <?= BLocale::_("Total"); ?>: $<span id="cart-subtotal"><?=$itemPrice?></span></a></li>