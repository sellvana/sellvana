<?php $prod = $this->prod; ?>
<label for="qty">Qty:</label>
<input type="text" name="qty" id="qty" maxlength="12" value="1" title="Qty" class="input-text qty">
                            <button type="submit" title="Add to Cart" class="button btn-add-to-cart"
                                onclick="add_cart(<?=$prod->id?>, this.form.qty.value);" name="add2cart" value="<?=$prod->id?>"
                                ><span>+ Add to Cart</span></button>