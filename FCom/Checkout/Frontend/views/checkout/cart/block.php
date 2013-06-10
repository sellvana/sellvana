<?php
$cart = FCom_Sales_Model_Cart::i()->sessionCart();
$cartQty = $cart->itemQty();
$items = $cart->recentItems();
$checkoutButton = FCom_Checkout_Frontend_CheckoutMethod::i()->getCartCheckoutButton();
?>
<div class="mini-cart <?php echo !$cartQty?' empty-cart':'cart-filled';?>">
    <div class="mini-cart-title">
        <a href="<?=BApp::href('cart')?>" rel="nofollow" class="title">
        <em class="icon icon-header-cart"></em><em class="icon icon-header-arrow"></em>
        Cart: <?=$this->_('%s', $cartQty)?></a>
    </div>
<?php if ($cartQty): ?>
    <div class="mini-cart-content">
        <header>
            <span class="title"><?=$this->_('Recently Added')?></span>
        </header>
        <ol>
<?php foreach ($items as $item): $p = $item->product() ?>
            <li>
                <a href="<?=$p->url()?>">
                    <img src="<?=$p->thumbUrl(50)?>" width="50" height="50" alt="" class="product-image"/>
                    <span class="product-name"><?=$this->q($p->product_name)?></span>
                    <span class="info"><?=$this->_('Qty')?> <?=number_format($item->qty,0)?></span>
                    <div class="price-box">
                        <span class="price-range">$<?=number_format($item->rowTotal(),2)?></span>
                    </div>
                </a>
            </li>
<?php endforeach ?>
        </ol>
        <div class="subtotal">
            <?=$this->_('Subtotal')?>: $<?=number_format($cart->subtotal)?>
            <a href="<?=$checkoutButton['href']?>" class="button btn-aux btn-sz1"><span><?=$this->_('Checkout')?></span></a>
        </div>
    </div>
<?php endif ?>
</div>