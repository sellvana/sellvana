<?php $prod = $this->prod; ?>
<button type="submit" title="Add to Wishlist" class="button btn-add-to-cart"
                                    onclick="add_wishlist(<?=$prod->id?>)" name="add2wishlist" value="<?=$prod->id?>"
                                    ><span>+ Add to Wishlist</span></button>