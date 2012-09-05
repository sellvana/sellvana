<?=$this->view('catalog/product/pager')->set('state', $this->products_data['state'])?>
<?php if (!$this->products_data['state']['c']): ?>
    <p class="note-msg"><?= BLocale::_("There are no products matching the selection") ?>.</p>

<?php else: ?>
	<?=$this->view('catalog/compare/block')?>
	<div class="product-listing">
	    <ul>
                <?php foreach ($this->products as $p): ?>
                <li class="item">
        		<img src="<?=$this->q($p->thumbUrl(125, 125))?>" width="160" height="160" class="product-image" alt="<?=$this->q($p->product_name)?>"/>
		        <span class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></span>
		        <span class="rating">
		            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
		            (<a href="#">16</a>)
		        </span>
		        <div class="price-box">
		        	<div class="old-price"><span class="title">Was:</span><span class="price">$<?=number_format($p->base_price,2)?></span></div>
		        	<div class="new-price"><span class="title">Now:</span><span class="price">$<?=number_format($p->base_price,2)?></span></div>
		        </div>
		       	<button class="button btn-add-to-cart" onclick="FCom.cart.add(<?=$p->id?>, 1)"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
		        <p><label for="wishlist-<?=$p->id?>"><input type="checkbox" id="wishlist-<?=$p->id?>"/> Wishlist</label></p>
		        <p><label for="compare-<?=$p->id?>"><input type="checkbox" name="compare" class="compare-checkbox" id="compare-<?=$p->id?>" value="<?=$p->id?>"/> Compare</label></p>
	        </li>
                <?php endforeach; ?>
	   	</ul>
    </div>
<?php endif ?>



