<?php
$cart = FCom_Checkout_Model_Cart::sessionCart();
$itemPrice = round($cart->subtotal,2);
$itemNum = ceil($cart->item_num);
?>
<li class="header-sup-cart"><a href="<?=BApp::href('cart')?>">Your Cart <span class="count">(<?=$itemNum?>)</span>
| Total: $<?=$itemPrice?></a></li>