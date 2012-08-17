<?php
$s = $this->state;
if(empty($s['p'])) $s['p'] = 0;

$psOptions = array(25, 50, 100, 500, 30000);
$sortOptions = $this->sort_options ? $this->sort_options : array(
    '' => 'Sort...',
    'relevance' => 'Relevance',
    'product_name|asc' => 'Product Name (A-Z)',
    'product_name|desc' => 'Product Name (Z-A)',
    'manuf_sku|asc' => 'Manuf SKU (A-Z)',
    'manuf_sku|desc' => 'Manuf SKU (Z-A)',
    'base_price|asc' => 'Price (Lower first)',
    'base_price|desc' => 'Price (Higher first)',
);

?>


<div style="">

    <form id="product_list_pager" name="product_list_pager" autocomplete="off" method="get" action="">
        <?php if (!empty($s['available_facets'])): ?>
            <?php foreach($s['available_facets'] as $label => $data):?>
                <?php foreach ($data as $obj): ?>
                        <?php if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
                            <input type="hidden" name="<?=$obj->param?>" value="<?=$obj->name?>" />
                        <?php endif; ?>
                <?php endforeach ?>
            <?php endforeach; ?>
        <?php endif; ?>

    <div class="rows f-left">
        <input type="text" name="q" id="query" autocomplete="off" value="<?=$this->q(BRequest::i()->get('q'))?>"/>
        <input type="submit" value="<?= BLocale::_("Search") ?>">
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


    <div class="rows f-left" style="margin-left: 10px;">
    <strong class="count"> <?= BLocale::_("Found") ?>: <?=!empty($s['c'])?$s['c']:0?></strong>.

    <label><?= BLocale::_("Page") ?>:</label>
    <?php if ($s['p']>1): ?>
        <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('p' => $s['p']-1))?>" class="arrow-left" >&lt;</a>
    <?php endif ?>
        <?=$s['p']?> of <?=$s['mp']?>
    <?php if ($s['p']<$s['mp']): ?>
        <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('p' => $s['p']+1))?>" class="arrow-right" >&gt;</a>
    <?php endif ?>

	</div>
    </form>
</div>

