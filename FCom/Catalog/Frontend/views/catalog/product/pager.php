<?php
$s = $this->state;
if(empty($s['p'])) $s['p'] = 0;

$psOptions = array(25, 50, 100, 500);
$sortOptions = $this->sort_options ? $this->sort_options : array(
    'product_name|asc' => 'Product Name (A-Z)',
    'product_name|desc' => 'Product Name (Z-A)',
    'manuf_sku|asc' => 'Manuf SKU (A-Z)',
    'manuf_sku|desc' => 'Manuf SKU (Z-A)',
    'base_price|asc' => 'Price (Lower first)',
    'base_price|desc' => 'Price (Higher first)',
);

?>
<div class="pager">
    <form id="product_list_pager" name="product_list_pager" autocomplete="off" method="get" action="">
        <?php if (!empty($this->query)):?>
            <input type="hidden" name="q" value="<?=$this->query?>" />
        <?php endif; ?>
        <?=$this->hook('catalog/product/pager.fields', array('pager_view'=>$this))?>

	    <div class="pager-count">
		    <strong class="count"> <?= BLocale::_("Found") ?>: <?=!empty($s['c'])?$s['c']:0?></strong>.&nbsp;&nbsp;
		    <label><?= BLocale::_("Page") ?>:</label>
		    <?php if ($s['p']>1): ?>
		        <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('p' => $s['p']-1))?>" class="arrow-left"><big>&laquo;</big></a>
		    <?php endif ?>
		        <input type="text" name="p" value="<?=$s['p']?>" size="3"/>
                <button type="button" class="button btn-aux" onclick="this.form.submit()"><span>Go</span></button>
                 of <?=$s['mp']?>
		    <?php if ($s['p']<$s['mp']): ?>
		        <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('p' => $s['p']+1))?>" class="arrow-right"><big>&raquo;</big></a>
		    <?php endif ?>
		</div>
    <div class="pager-rows">
	    <label><?= BLocale::_("Rows") ?>:</label>
	    <select name="ps" onchange="this.form.submit()" class="select2">
	<?php foreach ($psOptions as $i): ?>
	        <option value="<?=$i?>" <?=$s['ps']==$i?'selected':''?>><?=$i?></option>
	<?php endforeach ?>
	    </select>
	</div>
    <div class="pager-sort">
    	<label><?= BLocale::_("Sort") ?>:</label> <?=$s['sc']?>
    	<select name="sc" onchange="this.form.submit()" class="select2">
<?php foreach ($sortOptions as $k=>$v): ?>
        	<option value="<?=$k?>" <?=$s['sc']==$k?'selected':''?>><?=BLocale::_($v)?></option>
<?php endforeach ?>
    	</select>
    </div>
    <div class="pager-layout">
	    <span class="options-select">
                <?php if('grid' == BRequest::get('view')): ?>
                    <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('view' => 'grid'))?>" class="option grid active"><span class="icon"></span><?= BLocale::_("View as Grid") ?></a>
                    <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('view' => 'list'))?>" class="option list"><span class="icon"></span><?= BLocale::_("View as List") ?></a>
                <?php else: ?>
                    <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('view' => 'grid'))?>" class="option grid"><span class="icon"></span><?= BLocale::_("View as Grid") ?></a>
	    	<a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('view' => 'list'))?>" class="option list active"><span class="icon"></span><?= BLocale::_("View as List") ?></a>
                <?php endif; ?>
	    </span>
	</div>
    </form>
</div>

