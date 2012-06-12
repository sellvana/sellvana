Payment methods

<form action="<?=BApp::href('checkout/payment')?>" method="post">
    <h4>Payment method:</h4>
<ul>
    <li><input type="radio" name="payment_method" value="credit_card"
        <?= 'credit_card' == $this->cart->payment_method ? 'checked' : '' ?>>
        Credit Card</li>
    <li><input type="radio" name="payment_method" value="paypal"
               <?= 'paypal' == $this->cart->payment_method ? 'checked' : '' ?>>
        PayPal</li>
</ul>
<br/>
    <input type="submit" value="continue to checkout">
</form>