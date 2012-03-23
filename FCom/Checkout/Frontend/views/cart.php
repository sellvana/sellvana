<?
$loggedIn = FCom_Customer_Model_User::i()->isLoggedIn();
?>
    <div class="col-main">
        <div class="page-title category-title">
            <h1>Cart</h1>
        </div>
<? if (!$this->cart->items()): ?>

    <p class="note-msg">There are no products matching the selection.</p>

<? else: ?>

    <form name="cart" action="<?=BApp::href('checkout/cart')?>" method="post">
        <table class="product-list">
            <col width="30"/>
            <col width="60"/>
            <col/>
            <col width="180"/>
            <col width="70"/>
            <col width="70"/>
            <thead>
                <tr>
                    <td>Remove</td>
                    <td colspan="2">Product</td>
                    <td>Price</td>
                    <td>Qty</td>
                    <td>Subtotal</td>
                </tr>
            </thead>
            <tbody>
<? foreach ($this->cart->items() as $item): $p = $item->product() ?>
                <tr id="tr-product-<?=$p->id?>">
                    <td class="first a-center">
                        <label><input type="checkbox" name="remove[]" class="remove-checkbox" value="<?=$item->id?>"></label>
                    </td>
                    <td>
                        <img src="<?=$this->q($p->thumbUrl(85, 60))?>" width="85" height="60" class="product-img" alt="<?=$this->q($p->product_name)?>"/>
                    </td>
                    <td>
                        <h3 class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></h3>
                        <span class="manuf-name"><?=$this->q($p->manuf()->manuf_name)?></span>
                        <span class="sku">Part #: <?=$this->q($p->manuf_sku)?></span>
                        <span class="rating">
                            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
                            3.5 of 5 (<a href="#">16 reviews</a>)
                        </span>
                    </td>
                    <td class="actions last a-left">
                        <div class="price-box <?=$loggedIn?'logged-in':'logged-out'?>">
                            <? if ($loggedIn): ?><span class="availability in-stock">In Inventory</span><? endif ?>
                            <span class="price-label">As low as</span>
                            <p><span class="price">$<?=number_format($p->base_price)?></span><span class="supplier">Darby Dental</span></p>
                            <span class="price-range"><strong><a href="#" class="vendor-count">13 Vendors</a>
                            <span class="tooltip">
TEST
                            </span></strong>: $24-$49</span>
                        </div>
                    </td>
                    <td>
                        <input type="text" size="3" name="qty[<?=$item->id?>]" value="<?=$item->qty*1?>"/>
                    </td>
                    <td>
                        <span class="price">$<?=number_format($item->rowTotal(), 2)?></span>
                    </td>
                </tr>
<? endforeach ?>
            </tbody>
            <tfoot>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td><input type="submit" class="button" value="Update Cart"/></td>
                <td>$<span class="cart-subtotal"><?=number_format($cart->subtotal)?></span></td>
            </tfoot>
        </table>
    </form>
<? endif ?>

    </div>
<script>
$('.vendor-count').tooltip({effect:'slide'});
</script>
