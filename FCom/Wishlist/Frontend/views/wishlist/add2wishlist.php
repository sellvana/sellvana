<?php
$prod = $this->prod;
$loggedIn = FCom_Customer_Model_Customer::isLoggedIn();
if (!$loggedIn) {
    return;
}
?>
<button type="submit" title="Add to Wishlist" class="button btn-add-to-cart"
                                    onclick="add_wishlist(<?=$prod->id?>)" name="add2wishlist" value="<?=$prod->id?>"
                                    ><span>+ Add to Wishlist</span></button>