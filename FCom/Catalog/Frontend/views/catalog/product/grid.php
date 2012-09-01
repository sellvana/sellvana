<?=$this->view('catalog/product/pager')->set('state', $this->products_data['state'])?>
<?php if (!$this->products_data['state']['c']): ?>
    <p class="note-msg"><?= BLocale::_("There are no products matching the selection") ?>.</p>

<?php else: ?>
	<?=$this->view('catalog/compare/block')?>
	<div class="product-listing">
	    <ul>
	        <li class="item">
        		<img src="#" width="160" height="160" class="product-image" alt="#"/>
		        <span class="product-name"><a href="#">Product Name</a></span>
		        <span class="rating">
		            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
		            (<a href="#">16</a>)
		        </span>
		        <div class="price-box">
		        	<div class="old-price"><span class="title">Was:</span><span class="price">$399.99</span></div>
		        	<div class="new-price"><span class="title">Now:</span><span class="price">$399.99</span></div>
		        </div>
		       	<button class="button btn-add-to-cart" onclick="#"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
		        <p><label for="#"><input type="checkbox"/> Wishlist</label></p>
		        <p><label for="#"><input type="checkbox"/> Compare</label></p>
	        </li>
	        <li class="item">
        		<img src="#" width="160" height="160" class="product-image" alt="#"/>
		        <span class="product-name"><a href="#">Product Name</a></span>
		        <span class="rating">
		            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
		            (<a href="#">16</a>)
		        </span>
		        <div class="price-box">
		        	<div class="old-price"><span class="title">Was:</span><span class="price">$399.99</span></div>
		        	<div class="new-price"><span class="title">Now:</span><span class="price">$399.99</span></div>
		        </div>
		       	<button class="button btn-add-to-cart" onclick="#"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
		        <p><label for="#"><input type="checkbox"/> Wishlist</label></p>
		        <p><label for="#"><input type="checkbox"/> Compare</label></p>
	        </li>
	        <li class="item">
        		<img src="#" width="160" height="160" class="product-image" alt="#"/>
		        <span class="product-name"><a href="#">Product Name</a></span>
		        <span class="rating">
		            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
		            (<a href="#">16</a>)
		        </span>
		        <div class="price-box">
		        	<div class="old-price"><span class="title">Was:</span><span class="price">$399.99</span></div>
		        	<div class="new-price"><span class="title">Now:</span><span class="price">$399.99</span></div>
		        </div>
		       	<button class="button btn-add-to-cart" onclick="#"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
		        <p><label for="#"><input type="checkbox"/> Wishlist</label></p>
		        <p><label for="#"><input type="checkbox"/> Compare</label></p>
	        </li>
	        <li class="item">
        		<img src="#" width="160" height="160" class="product-image" alt="#"/>
		        <span class="product-name"><a href="#">Product Name</a></span>
		        <span class="rating">
		            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
		            (<a href="#">16</a>)
		        </span>
		        <div class="price-box">
		        	<div class="old-price"><span class="title">Was:</span><span class="price">$399.99</span></div>
		        	<div class="new-price"><span class="title">Now:</span><span class="price">$399.99</span></div>
		        </div>
		       	<button class="button btn-add-to-cart" onclick="#"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
		        <p><label for="#"><input type="checkbox"/> Wishlist</label></p>
		        <p><label for="#"><input type="checkbox"/> Compare</label></p>
	        </li>
	        <li class="item">
        		<img src="#" width="160" height="160" class="product-image" alt="#"/>
		        <span class="product-name"><a href="#">Product Name</a></span>
		        <span class="rating">
		            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
		            (<a href="#">16</a>)
		        </span>
		        <div class="price-box">
		        	<div class="old-price"><span class="title">Was:</span><span class="price">$399.99</span></div>
		        	<div class="new-price"><span class="title">Now:</span><span class="price">$399.99</span></div>
		        </div>
		       	<button class="button btn-add-to-cart" onclick="#"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
		        <p><label for="#"><input type="checkbox"/> Wishlist</label></p>
		        <p><label for="#"><input type="checkbox"/> Compare</label></p>
	        </li>
	        <li class="item">
        		<img src="#" width="160" height="160" class="product-image" alt="#"/>
		        <span class="product-name"><a href="#">Product Name</a></span>
		        <span class="rating">
		            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
		            (<a href="#">16</a>)
		        </span>
		        <div class="price-box">
		        	<div class="old-price"><span class="title">Was:</span><span class="price">$399.99</span></div>
		        	<div class="new-price"><span class="title">Now:</span><span class="price">$399.99</span></div>
		        </div>
		       	<button class="button btn-add-to-cart" onclick="#"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
		        <p><label for="#"><input type="checkbox"/> Wishlist</label></p>
		        <p><label for="#"><input type="checkbox"/> Compare</label></p>
	        </li>
	        <li class="item">
        		<img src="#" width="160" height="160" class="product-image" alt="#"/>
		        <span class="product-name"><a href="#">Product Name</a></span>
		        <span class="rating">
		            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
		            (<a href="#">16</a>)
		        </span>
		        <div class="price-box">
		        	<div class="old-price"><span class="title">Was:</span><span class="price">$399.99</span></div>
		        	<div class="new-price"><span class="title">Now:</span><span class="price">$399.99</span></div>
		        </div>
		       	<button class="button btn-add-to-cart" onclick="#"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
		        <p><label for="#"><input type="checkbox"/> Wishlist</label></p>
		        <p><label for="#"><input type="checkbox"/> Compare</label></p>
	        </li>
	        <li class="item">
        		<img src="#" width="160" height="160" class="product-image" alt="#"/>
		        <span class="product-name"><a href="#">Product Name</a></span>
		        <span class="rating">
		            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
		            (<a href="#">16</a>)
		        </span>
		        <div class="price-box">
		        	<div class="old-price"><span class="title">Was:</span><span class="price">$399.99</span></div>
		        	<div class="new-price"><span class="title">Now:</span><span class="price">$399.99</span></div>
		        </div>
		       	<button class="button btn-add-to-cart" onclick="#"><em class="icon"></em><span><?= BLocale::_("Add to Cart") ?></span></button>
		        <p><label for="#"><input type="checkbox"/> Wishlist</label></p>
		        <p><label for="#"><input type="checkbox"/> Compare</label></p>
	        </li>
	   	</ul>
    </div>
<?php endif ?>



