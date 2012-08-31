<?
$loggedIn = FCom_Customer_Model_Customer::isLoggedIn();
?>
<?php foreach ($this->products as $p): ?>
<tr class="item" id="tr-product-<?=$p->id?>">
    <td class="first a-center">
        <label class="compare-label"><input type="checkbox" name="compare" class="f-none" value="<?=$p->id?>"> <?= BLocale::_("Compare") ?></label>
    </td>
    <td>
        <img src="<?=$this->q($p->thumbUrl(125, 125))?>" width="125" height="125" class="product-image" alt="<?=$this->q($p->product_name)?>"/>
    </td>
    <td>
        <span class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></span>
        <div class="price-box">
        	<span class="price"><?= BLocale::_("Price") ?> : $<?=$this->q(number_format($p->base_price,0))?></span>
        </div>
        <span class="rating">
        </span>
        <div class="product-description">
        	<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap.</p>
        </div>
    </td>
    <td class="actions last a-left">
       	<button class="button btn-add-to-cart" onclick="FCom.cart.add(<?=$p->id?>, 1)"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
        <p><label for="#"><input type="checkbox"/> Wishlist</label></p>
        <p><label for="#"><input type="checkbox"/> Compare</label></p>
    </td>
</tr>
<?php endforeach ?>