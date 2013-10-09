<?php
$model = FCom_GoogleCheckout::i();
$model->setConfig(BConfig::i()->get('modules/FCom_GoogleCheckout'));
?>

<form method="POST" action="<?php echo $model->getFormUrl(); ?>">
    <input type="hidden" name="cart" value="<?php echo $model->getCartValueEncoded(); ?>">
    <input type="hidden" name="signature" value="<?php echo $model->getSignatureValueEncrypted(); ?>">
    <input type="image" name="Google Checkout" alt="Fast checkout through Google"
           src="<?php echo $model->getButtonSrc(); ?>"
           height="<?php echo $model->getButtonHeight(); ?>" width="<?php echo $model->getButtonWidth(); ?>">
</form>