<?php $prod = $this->prod; ?>
<div class="add-to-cart-box">
	<label for="qty"><?= BLocale::_("Qty") ?>:</label>
	<input type="text" name="qty" id="qty" maxlength="12" value="1" title="Qty" class="input-text qty">
	<button type="submit" title="<?= BLocale::_("Add to Cart") ?>" class="button btn-add-to-cart" onclick="FCom.cart.add(<?=$prod->id?>, this.form.qty.value);" name="add2cart" value="<?=$prod->id?>"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
</div>