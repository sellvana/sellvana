<?
$loggedIn = AUser::isLoggedIn();
AManuf::i()->cachePreloadFrom($this->products, 'manuf_id');
?>
<? foreach ($this->products as $p): ?>
<tr id="tr-product-<?=$p->id?>">
    <td class="first a-center">
        <label class="compare-label"><input type="checkbox" name="compare" class="compare-checkbox" value="<?=$p->id?>"> Compare</label>
    </td>
    <td>
        <img src="<?=$this->q($p->thumbUrl(85, 60))?>" width="85" height="60" class="product-img" alt="<?=$this->q($p->product_name)?>"/>
    </td>
    <td>
        <h3 class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></h3>
        <span class="sku">Part #: <?=$this->q($p->manuf_sku)?></span>
        <span class="manuf-name"><?=$this->q($p->manuf()->manuf_name)?></span>
        <span class="rating">
            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
            3.5 of 5 (<a href="#">16 reviews</a>)
        </span>
    </td>
    <td class="actions last a-left">
        <div class="price-box <?=$loggedIn?'logged-in':'logged-out'?>">
            <? if ($loggedIn):?><span class="availability in-stock">In Inventory</span><? endif ?>
            <span class="price-label">As low as</span>
            <p><span class="price">$<?=number_format($p->base_price)?></span><span class="supplier">Darby Dental</span></p>
            <div class="price-range">
                <strong><a href="#" class="vendor-count">13 Vendors</a></strong>: $24-$49
            </div>
            <div class="tt tooltip">
                <div class="tt-arrow"></div>
                <div class="tt-header">13 Vendors</div>
                <div class="tt-content">
                    <ul>
                        <li><span class="label">Darby Dental</span><span class="lowest-price">Lowest Price</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                    </ul>
                </div>
            </div>
            <button class="button btn-add-to-cart" onclick="dentevaCart.add(<?=$p->id?>)">+ Add to Cart</button>
        </div>
    </td>
</tr>
<? endforeach ?>