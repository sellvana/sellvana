<h2>Review the information below then click "Place your order"</h2>
<form action="<?=BApp::href('checkout')?>" method="post">
<input type="submit" name="place_order" value="Place your order">
<input type="submit" name="update" value="Apply changes">

<?php if ($this->messagesHtml()) :?>
<br/>
<span style="color:red"><?php echo $this->messagesHtml() ?></span>
<?php endif; ?>

<br/><br/>

<h4>Shipping to:</h4>
<a href="<?=BApp::href('checkout/address?t=s')?>">Change</a><br/>
<?=$this->shippingAddress?><br>
<br>


<?php if (!empty($this->shippingMethods)) :?>
    <h4>Shipping Options:</h4>

    <ul>
        <?php foreach($this->shippingMethods as $shippingMethod => $shippingClass): ?>
        <li><?=$shippingClass->getDescription()?> (<?=$shippingClass->getEstimate()?>)
            <ul>
            <?php foreach($shippingClass->getServicesSelected() as $serviceKey => $service) :?>
                <li style="margin-left: 20px;">
                    <input type="radio" name="shipping" value="<?=$shippingMethod.':'.$serviceKey?>"
                    <?= $shippingMethod == $this->cart->shipping_method &&
                            $serviceKey == $this->cart->shipping_service ? 'checked' : '' ?>> <?=$service?></li>
            <?php endforeach; ?>
            </ul>
        </li>
        <?php endforeach; ?>
    </ul>
    <input type="submit" name="update" value="Apply changes">
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
                        <span class="price">$<?=number_format($p->base_price, 2)?>
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

<?php if (!empty($this->totals)) : ?>
    <ul>
    <?php foreach($this->totals as $totals) :?>
        <li><?=$totals['options']['label']?>: $<?=number_format($totals['total'], 2)?>
            <?php if (!empty($totals['error'])) :?>
                (<span style="color:red"><?=$totals['error']?></span>)
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
        <li>Grand total: <?=$this->cart->calc_balance?></li>
    </ul>
<?php endif; ?>
<br/><br/>

<b>Coupon, discount or promo code:</b>
<input type="text" name="discount_code"> <input type="submit" name="coupon_submit" value="Apply">
<br/><br/>

<h4>Payment method:</h4>
<?php if (!empty($this->paymentMethod)) :?>
    <a href="/checkout/payment">Change</a><br/>

    <b><?=$this->paymentClass->getName()?></b><br/>
    <?= $this->view($this->paymentMethod.'/form')->set('paymentDetails', $this->paymentDetails);?>
    <br/><br/>
<?php else: ?>
    <a href="/checkout/payment">Select payment method</a><br/>
<?php endif; ?>


<h4>Billing address</h4>
<a href="<?=BApp::href('checkout/address?t=b')?>">Change</a><br/>
<?=$this->billingAddress?><br><br>

<?php if ($this->guest) :?>
<label for="#">Create an account?</label>
<input type="checkbox" name="create_account" value="1" class="required"><br/>
<label for="#">E-mail</label>
<input type="text" name="account[email]" value="<?=$this->billingAddressObject->email?>" class="required"><br/>
<label for="#">Password</label>
<input type="password" name="account[password]" class="required" id="model-password"/><br/>
<label for="#">Confirm Password </label>
<input type="password" name="account[password_confirm]" class="required" equalto="#model-password"/><br/>
<?php endif; ?>

<input type="submit" name="update" value="Apply changes">

</form>