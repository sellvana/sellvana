<?php
$prod = $this->prod;
$loggedIn = FCom_Customer_Model_Customer::isLoggedIn();
if (!$loggedIn) {
    return;
}
?>
<button type="submit" title="<?= BLocale::_("Add to Wishlist") ?>" class="btn btn-primary"
                                    onclick="add_wishlist(<?=$prod->id?>)" name="add2wishlist" value="<?=$prod->id?>"
                                    >+ <?= BLocale::_("Add to Wishlist") ?></button>