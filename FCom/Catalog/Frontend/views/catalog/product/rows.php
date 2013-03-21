<?php
$loggedIn = FCom_Customer_Model_Customer::isLoggedIn();
?>
<?php foreach ($this->products as $p): ?>
<tr class="item" id="tr-product-<?=$p->id?>">
    <td>
        <img src="<?=$this->q($p->thumbUrl(125, 125))?>" width="125" height="125" class="product-image" alt="<?=$this->q($p->product_name)?>"/>
    </td>
    <td>
        <span class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></span>
        <span class="rating">
            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
            3.5 of 5 (<a href="#">16 reviews</a>)
        </span>
        <div class="price-box">
        	<div class="old-price"><span class="title">Was:</span><span class="price">$<?=$this->q(number_format($p->base_price,2))?></span></div>
        	<div class="new-price"><span class="title">Now:</span><span class="price">$<?=$this->q(number_format($p->base_price,2))?></span></div>
        </div>
        <div class="product-description">
        	<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap.</p>
        </div>
    </td>
    <td class="actions last a-left">
       	<button class="button btn-add-to-cart" onclick="FCom.cart.add(<?=$p->id?>, 1)"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
        <p><label for="wishlist-<?=$p->id?>"><input type="checkbox" id="wishlist-<?=$p->id?>"/> Wishlist</label></p>
        <p><label for="compare-<?=$p->id?>"><input type="checkbox" name="compare" class="compare-checkbox" id="compare-<?=$p->id?>" value="<?=$p->id?>"/> Compare</label></p>
    </td>
</tr>
<?php endforeach ?>