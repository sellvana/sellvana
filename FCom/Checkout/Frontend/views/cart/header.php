<?php
$cart = FCom_Checkout_Model_Cart::sessionCart();
$cartQty = $cart->itemQty();
$items = $cart->recentItems();
?>
<div class="mini-cart <?php echo !$cartQty?' empty-cart':'';?>">
    <div class="mini-cart-title">
        <a href="<?=BApp::href('cart')?>" rel="nofollow" class="title">
        <em class="icon icon-header-cart"></em><em class="icon icon-header-arrow"></em>
        <?=$cartQty?> item(s) in cart</a>
    </div>
    <div class="mini-cart-content">
<?php if (!$cartQty): ?>
        <header>
            <span class="title">No items in cart</span>
        </header>
<?php else: ?>
        <header>
            <span class="title">Recently Added</span>
            <a href="<?=BApp::href('cart')?>" class="button btn-aux btn-sz1"><span>View Cart</span></a>
        </header>
        <ol>
<?php foreach ($items as $item):  ?>
            <li>
                <a href="#">
                    <img src="<?=$item->thumb_url?>" width="50" height="50" alt="" class="product-img"/>
                    <span class="product-name"><?=$this->q($item->product_name)?></span>
                    <span class="info"><strong>Qty <?=$item->qty?></strong> - Part # <?=$this->q($item->manuf_sku)?></span>
                    <div class="price-box">
                        <span class="price-range">$<?=number_format($item->qty*$item->min_price,2).($item->min_price!=$item->max_price ? ' - $'.number_format($item->max_price*$item->qty) : '')?></span>
                    </div>
                </a>
            </li>
<?php endforeach ?>
        </ol>
        <div class="subtotal">
            Subtotal: $<?=number_format($cart->min_subtotal).($cart->max_subtotal!=$cart->min_subtotal ? ' - $'.number_format($cart->max_subtotal) : '')?>
        </div>
<?php endif ?>
    </div>
</div>