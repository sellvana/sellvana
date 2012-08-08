<?php
$prod = $this->prod;
$loggedIn = FCom_Customer_Model_Customer::isLoggedIn();
if (!$loggedIn) {
    return;
}
?>
<button type="submit" title="<?= BLocale::_("Add to Wishlist"); ?>" class="button btn-add-to-cart"
                                    onclick="add_wishlist(<?=$prod->id?>)" name="add2wishlist" value="<?=$prod->id?>"
                                    ><span>+ <?= BLocale::_("Add to Wishlist"); ?></span></button>