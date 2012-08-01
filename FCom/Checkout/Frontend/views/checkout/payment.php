<?= BLocale::_("Payment methods"); ?>

<form action="<?=BApp::href('checkout/payment')?>" method="post">
    <h4><?= BLocale::_("Payment method"); ?>:</h4>
<ul>
    <?php foreach($this->payment_methods as $method => $class) :?>
    <li><input type="radio" name="payment_method" value="<?=$method?>"
        <?= $method == $this->cart->payment_method ? 'checked' : '' ?>>
        <?=$class->getName()?></li>
    <?php endforeach; ?>
</ul>
<br/>
    <input type="submit" value="continue to checkout">
</form>