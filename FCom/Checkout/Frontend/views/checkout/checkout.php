<h2><?= BLocale::_("Review the information below then click") ?> "Place your order"</h2>
<form action="<?=BApp::href('checkout')?>" method="post">
<input type="submit" name="place_order" value="<?= BLocale::_("Place your order") ?>">
<input type="submit" name="update" value="<?= BLocale::_("Apply changes") ?>">

<?php if ($this->messagesHtml()) :?>
<br/>
<span style="color:red"><?php echo $this->messagesHtml() ?></span>
<?php endif; ?>

<br/><br/>

<h4><?= BLocale::_("Shipping to") ?>:</h4>

<?=$this->shippingAddress?>
<?php if($this->guest_checkout) :?>
    <a href="<?=BApp::href('checkout/address?t=s')?>"><?= BLocale::_("Change") ?></a>
<?php else :?>
    <a href="<?=BApp::href('customer/address/choose?t=s')?>"><?= BLocale::_("Change") ?></a>
<?php endif; ?>
<br><br>


<?php if (!empty($this->shippingMethods)) :?>
    <h4><?= BLocale::_("Shipping Options") ?>:</h4>

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
    <input type="submit" name="update" value="<?= BLocale::_("Apply changes") ?>">
    <br/><br/>
<?php endif; ?>

<table class="product-list">
            <col width="500"/>
            <col width="70"/>
            <col width="70"/>
            <col width="70"/>
            <thead>
                <tr>
                    <td><?= BLocale::_("Product") ?></td>
                    <td><?= BLocale::_("Price") ?></td>
                    <td><?= BLocale::_("Qty") ?></td>
                    <td><?= BLocale::_("Subtotal") ?></td>
                </tr>
            </thead>
            <tbody>
<?php foreach ($this->cart->items() as $item): $p = $item->product() ?>
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
<?php endforeach ?>
            </tbody>
            <tfoot>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tfoot>
        </table>
<br/>
<a href="/cart"><?= BLocale::_("Need to change quantities or delete") ?>?</a>
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
        <li><?= BLocale::_("Grand total") ?>: <?=$this->cart->calc_balance?></li>
    </ul>
<?php endif; ?>
<br/><br/>

<b><?= BLocale::_("Coupon, discount or promo code") ?>:</b>
<input type="text" name="discount_code"> <input type="submit" name="coupon_submit" value="<?= BLocale::_("Apply") ?>">
<br/><br/>

<h4><?= BLocale::_("Payment method") ?>:</h4>
<?php if (!empty($this->paymentMethod)) :?>
    <a href="/checkout/payment"><?= BLocale::_("Change") ?></a><br/>

    <i><?=$this->paymentClass->getName()?></i><br/>
    <?= $this->view($this->paymentMethod.'/form')->set('paymentDetails', $this->paymentDetails);?>
    <br/><br/>
<?php else: ?>
    <a href="/checkout/payment" style="color:red"><?= BLocale::_("Select payment method") ?></a><br/>
<?php endif; ?>


<h4><?= BLocale::_("Billing address") ?></h4>

<?=$this->billingAddress?>
<?php if($this->guest_checkout) :?>
    <a href="<?=BApp::href('checkout/address?t=b')?>"><?= BLocale::_("Change") ?></a>
<?php else :?>
    <a href="<?=BApp::href('customer/address/choose?t=b')?>"><?= BLocale::_("Change") ?></a>
<?php endif; ?>
<br><br>

<?php if ($this->guest) :?>
<label for="#"><?= BLocale::_("Create an account") ?>?</label>
<input type="checkbox" name="create_account" value="1" class="required"><br/>
<label for="#">E-mail</label>
<input type="text" name="account[email]" value="<?=$this->billingAddressObject->email?>" class="required"><br/>
<label for="#"><?= BLocale::_("Password") ?></label>
<input type="password" name="account[password]" class="required" id="model-password"/><br/>
<label for="#"><?= BLocale::_("Confirm Password") ?> </label>
<input type="password" name="account[password_confirm]" class="required" equalto="#model-password"/><br/>
<?php endif; ?>

<input type="submit" name="update" value="<?= BLocale::_("Apply changes") ?>">

</form>