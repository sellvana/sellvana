<?
$loggedIn = FCom_Customer_Model_Customer::i()->isLoggedIn();
?>
    <div class="col-main">
        <div class="page-title category-title">
            <h1>Cart</h1>
        </div>
<? if (!$this->cart->items()): ?>

    <p class="note-msg">There are no products matching the selection.</p>

<? else: ?>

    <form name="cart" action="<?=BApp::href('cart')?>" method="post">
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
                    </td>
                    <td class="actions last a-left">
                        <div class="price-box <?=$loggedIn?'logged-in':'logged-out'?>">
                            <span class="price">$<?=number_format($p->base_price)?></span>
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
                <td>
                    <?php if ($this->redirectLogin) :?>
                        <a href="<?=BApp::href('checkout/login')?>">Checkout</a>
                    <?php else :?>
                        <a href="<?=BApp::href('checkout')?>">Checkout</a>
                    <?php endif; ?>
                </td>
                <td><input type="submit" class="button" value="Update Cart"/></td>
                <td>$<span class="cart-subtotal"><?=number_format($this->cart->subtotal)?></span></td>
            </tfoot>
        </table>
    </form>

    <form action="<?=BApp::href('cart')?>" method="post">
        <table>
            <?php if ($this->shipping_esitmate): ?>
            <tr>
                <td>Shipping estimate:<br/>
                    <?php if ($this->shipping_esitmate) :?>
                    <ul>
                        <?php foreach($this->shipping_esitmate as $estimate): ?>
                            <li><?=$estimate['description']?> (<?=$estimate['estimate']?>)</li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Post code: <input type="text" size="4" name="postcode" value=""/></td>
            </tr>
            <tr>
                <td><input type="submit" class="button" value="Estimate shipping"/></td>
            </tr>
        </table>
    </form>
<? endif ?>

    </div>
<script>
$('.vendor-count').tooltip({effect:'slide'});
</script>
