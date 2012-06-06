<h2>Review the information below then click "Place your order"</h2>
<form action="<?=BApp::href('checkout')?>" method="post">
<input type="submit" name="place_order" value="Place your order">

<br/><br/>

<h4>Shipping to:</h4>
<a href="<?=BApp::href('customer/address/shipping')?>">Change</a><br/>
<?=$this->shippingAddress?><br>
<br>



<h4>Shipping Options:</h4>

<ul>
    <li><input type="radio" name="shipping_method">Free Standard Shipping (3-5 days)</li>
    <li><input type="radio" name="shipping_method">UPS Shipping (2 days)</li>
    <li><input type="radio" name="shipping_method">Fedex Shipping (2 days)</li>
</ul>

<b>Estimated day delivery: June 07 2012</b>
<br/><br/>
<table class="product-list">
            <col width="500"/>
            <col width="70"/>
            <col width="70"/>
            <col width="70"/>
            <thead>
                <tr>
                    <td>Product</td>
                    <td>Price</td>
                    <td>Qty</td>
                    <td>Subtotal</td>
                </tr>
            </thead>
            <tbody>
<? foreach ($this->cart->items() as $item): $p = $item->product() ?>
                <tr id="tr-product-<?=$p->id?>">
                    <td>
                        <h3 class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></h3>
                    </td>
                    <td >
                        <span class="price">$<?=number_format($p->base_price)?>
                    </td>
                    <td >
                        <b><?=$item->qty*1?></b>
                    </td>
                    <td >
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
            </tfoot>
        </table>
<br/>
<a href="/cart">Need to change quantities or delete?</a>
<br/><br/>
<h4>Shipping Summary:</h4>

Items: $10<br>
Shipping and handling: $0<br>
Total before tax: $10<br>
Estimated tax: $0<br>
<b>Order total: $10</b><br/><br/>

<b>Coupon, discount or promo code:</b>
<input type="text" name="coupon"> <input type="submit" name="coupon_submit" value="Apply">
<br/><br/>

<h4>Payment method:</h4>
<a href="/checkout/payment">Change</a><br/>
Credit card<br/>
Visa<br/>
<br/><br/>
<h4>Billing address</h4>
<a href="<?=BApp::href('customer/address/billing')?>">Change</a><br/>
<?=$this->billingAddress?><br><br>

</form>