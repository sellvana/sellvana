<?
$loggedIn = FCom_Customer_Model_Customer::isLoggedIn();
?>
<? foreach ($this->products as $p): ?>
<tr id="tr-product-<?=$p->id?>">
    <td class="first a-center">
        <label class="compare-label"><input type="checkbox" name="compare" class="compare-checkbox" value="<?=$p->id?>"> <?= BLocale::_("Compare"); ?></label>
    </td>
    <td>
        <img src="<?=$this->q($p->thumbUrl(30, 30))?>" width="30" height="30" class="product-img" alt="<?=$this->q($p->product_name)?>"/>
    </td>
    <td>
        <h3 class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></h3>
        <span class="price"><?= BLocale::_("Price"); ?> : $<?=$this->q(number_format($p->base_price,0))?></span>
        <span class="rating">
        </span>
    </td>
    <td class="actions last a-left">
        <div class="price-box <?=(!empty($loggedIn))?'logged-in':'logged-out'?>">
            <button class="button btn-add-to-cart" onclick="add_cart(<?=$p->id?>, 1)">+ <?= BLocale::_("Add to Cart"); ?></button>
        </div>
    </td>
</tr>
<? endforeach ?>