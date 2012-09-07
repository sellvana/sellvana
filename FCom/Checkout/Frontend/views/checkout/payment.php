

<form action="<?=BApp::href('checkout/payment')?>" method="post" class="payment-method-form">
    <header class="page-title">
    	<h1 class="title"><?= BLocale::_("Payment method") ?></h1>
    </header>
    <fieldset>
		<ul class="form-group">
		    <?php foreach($this->payment_methods as $method => $class) :?>
		    <li><input type="radio" name="payment_method" value="<?=$method?>"
		        <?= $method == $this->cart->payment_method ? 'checked' : '' ?>>
		        <?=$class->getName()?></li>
		    <?php endforeach; ?>
		</ul>
    	<button type="submit" class="button"><span><?= BLocale::_("Continue to checkout") ?></span></button>
    </fieldset>
</form>