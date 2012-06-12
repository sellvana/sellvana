<h2>Review the information below then click "Place your order"</h2>
<form action="<?=BApp::href('checkout')?>" method="post">
<input type="submit" name="place_order" value="Place your order">
<input type="submit" name="update" value="Apply changes">

<br/><br/>

<h4>Shipping to:</h4>
<a href="<?=BApp::href('checkout/address?t=s')?>">Change</a><br/>
<?=$this->shippingAddress?><br>
<br>


<?php if ($this->shippingMethods) :?>
<h4>Shipping Options:</h4>

<ul>
    <?php foreach($this->shippingMethods as $shippingMethod => $shippingClass): ?>
    <li><input type="radio" name="shipping_method" value="<?=$shippingMethod?>"
        <?= $shippingMethod == $this->cart->shipping_method ? 'checked' : '' ?>>
        <?=$shippingClass->getDescription()?> (<?=$shippingClass->getEstimate()?>)</li>
    <?php endforeach; ?>
</ul>

<br/><br/>
<?php endif; ?>

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
                        <b><?=number_format($item->qty, 0)?></b>
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

<?=$this->cart->totalAsHtml();?>
<br/><br/>

<b>Coupon, discount or promo code:</b>
<input type="text" name="discount_code"> <input type="submit" name="coupon_submit" value="Apply">
<br/><br/>

<h4>Payment method:</h4>
<a href="/checkout/payment">Change</a><br/>
<?php if (empty($this->cart->payment_method) || 'credit_card' == $this->cart->payment_method) :?>
<b>Credit Card</b>
Card Type:<br/>
Visa <input type="radio" name="payment[card_type]" value="visa" />
MasterCard <input type="radio" name="payment[card_type]" value="master_card" />
<br/>
Card number: <input type="text" name="payment[card_number]" /><br/>
Name on card: <input type="text" name="payment[name_on_card]" /><br/>
Expires:
<select id="expiration_month" name="payment[expiration_month]">
<option value="">Choose...</option>
<option value="01">01</option>
<option value="02">02</option>
<option value="03">03</option>
<option value="04">04</option>
<option value="05">05</option>
<option value="06">06</option>
<option value="07">07</option>
<option value="08">08</option>
<option value="09">09</option>
<option value="10">10</option>
<option value="11">11</option>
<option value="12">12</option>
</select>
<select id="expiration_year" name="payment[expiration_ year]">
<option value="">Choose...</option>
<option value="2012">2012</option>
<option value="2013">2013</option>
<option value="2014">2014</option>
<option value="2015">2015</option>
<option value="2016">2016</option>
<option value="2017">2017</option>
<option value="2018">2018</option>
<option value="2019">2019</option>
<option value="2020">2020</option>
</select>
<br/>
CVV: <input type="text" name="payment[cvv]" /><br/>
<?php elseif ('paypal' == $this->cart->payment_method) :?>
<b>PayPal</b>
<?php endif; ?>
<br/><br/>
<h4>Billing address</h4>
<a href="<?=BApp::href('checkout/address?t=b')?>">Change</a><br/>
<?=$this->billingAddress?><br><br>

<input type="submit" name="update" value="Apply changes">

</form>