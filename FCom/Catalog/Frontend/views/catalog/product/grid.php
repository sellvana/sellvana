<?=$this->view('catalog/product/pager')->set('state', $this->products_data['state'])?>
<?php if (!$this->products_data['state']['c']): ?>
    <p class="note-msg"><?= BLocale::_("There are no products matching the selection") ?>.</p>

<?php else: ?>
	<?=$this->view('catalog/compare/block')?>
	<div class="product-listing">
	    <ul>
	        <li></li>
	   	</ul>
    </div>
<?php endif ?>
