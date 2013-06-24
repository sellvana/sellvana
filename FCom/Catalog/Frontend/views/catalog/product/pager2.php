<?php
$s = $this->state;
$psOptions = array(25, 50, 100, 500, 30000);
$sortOptions = $this->sort_options ? $this->sort_options : array(
    '' => 'Sort...',
    'product_name|asc' => 'Product Name (A-Z)',
    'product_name|desc' => 'Product Name (Z-A)',
    'local_sku|asc' => 'SKU (A-Z)',
    'local_sku|desc' => 'SKU (Z-A)',
    'base_price|asc' => 'Price (Lower first)',
    'base_price|desc' => 'Price (Higher first)',
);
?>
<form id="product_list_pager" name="product_list_pager" method="get" action="">

<div class="pager">
    <strong class="count"><?=$s['c']?> found.</strong>
    <input type="hidden" name="q" value="<?=$this->q(BRequest::i()->get('q'))?>"/>
    <div class="pages">
    <label><?= BLocale::_("Page") ?>:</label>
    <?php if ($s['p']>1): ?><a href="#" class="arrow-left" onclick="$(this).siblings('input[name=p]').val(<?=$s['p']-1?>); $(this).parents('form').submit()">&lt;</a><?php endif ?>
    <!--<select name="p" onchange="this.form.submit()">
<?php for ($i=1; $i<=$s['mp']; $i++): ?>
        <option value="<?=$i?>" <?=$s['p']==$i?'selected':''?>><?=$i?></option>
<?php endfor ?>
    </select>-->
    <input type="text" name="p" value="<?=$s['p']?>"/> of <?=$s['mp']?>
    <?php if ($s['p']<$s['mp']): ?><a href="#" class="arrow-right" onclick="$(this).siblings('input[name=p]').val(<?=$s['p']+1?>); $(this).parents('form').submit()">&gt;</a><?php endif ?>
	</div>
	<div class="rows f-right">
    <label><?= BLocale::_("Rows") ?>:</label> <select name="ps" onchange="this.form.submit()">
<?php foreach ($psOptions as $i): ?>
        <option value="<?=$i?>" <?=$s['ps']==$i?'selected':''?>><?=$i?></option>
<?php endforeach ?>
    </select>
	</div>
    <div class="sort-by f-right">
    <label><?= BLocale::_("Sort") ?>:</label> <select name="sc" onchange="this.form.submit()">
<?php foreach ($sortOptions as $k=>$v): ?>
        <option value="<?=$k?>" <?=$s['sc']==$k?'selected':''?>><?=$v?></option>
<?php endforeach ?>
    </select>
    </div>
</div>

</form>
