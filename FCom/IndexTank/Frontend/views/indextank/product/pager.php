<?php
$s = $this->state;
$price_ranges = $this->price_ranges;

$psOptions = array(25, 50, 100, 500, 30000);
$sortOptions = $this->sort_options ? $this->sort_options : array(
    '' => 'Sort...',
    'relevance' => 'Relevance',
    'base_price_asc' => 'Price (Lower first)',
    'base_price_desc' => 'Price (Higher first)',
);

?>
<form id="product_list_pager" name="product_list_pager" method="get" action="">

<div class="pager">
    <strong class="count"><?=$s['c']?> found.</strong>
    <input type="text" name="q" value="<?=$this->q(BRequest::i()->get('q'))?>"/>
    <input type="submit" value="Search">
    <div class="pages">
    <label>Page:</label>

    <? if ($s['p']>1): ?><a href="#" class="arrow-left" onclick="$(this).siblings('input[name=p]').val(<?=$s['p']-1?>); $(this).parents('form').submit()">&lt;</a><? endif ?>
    <!--<select name="p" onchange="this.form.submit()">
<? for ($i=1; $i<=$s['mp']; $i++): ?>
        <option value="<?=$i?>" <?=$s['p']==$i?'selected':''?>><?=$i?></option>
<? endfor ?>
    </select>-->
    <input type="text" name="p" value="<?=$s['p']?>"/> of <?=$s['mp']?>
    <? if ($s['p']<$s['mp']): ?><a href="#" class="arrow-right" onclick="$(this).siblings('input[name=p]').val(<?=$s['p']+1?>); $(this).parents('form').submit()">&gt;</a><? endif ?>
	</div>
	<div class="rows f-right">
    <label>Rows:</label> <select name="ps" onchange="this.form.submit()">
<? foreach ($psOptions as $i): ?>
        <option value="<?=$i?>" <?=$s['ps']==$i?'selected':''?>><?=$i?></option>
<? endforeach ?>
    </select>
	</div>
    <div class="sort-by f-right">
    <label>Sort:</label> <select name="sc" onchange="this.form.submit()">
<? foreach ($sortOptions as $k=>$v): ?>
        <option value="<?=$k?>" <?=$s['sc']==$k?'selected':''?>><?=$v?></option>
<? endforeach ?>
    </select>
    </div>
    <br/><br/>
    <div class="sort-by f-left">
    <label>Filter by price:</label><br/>
<? foreach ($s['filter'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE] as $range): ?>
        <input type="checkbox" name="f[<?=FCom_IndexTank_Index_Product::CT_PRICE_RANGE?>][]"
               value="<?=$range?>" onclick="this.form.submit()"
               <?=(in_array($range, $s['filter_selected'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE]))?'checked':''?>
               >  <?=$range?>
                (<?=$s['facets'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE][$range]?>) <br/>
<? endforeach ?>
    <br/>
    <label>Filter by brand:</label><br/>
<? foreach ($s['filter'][FCom_IndexTank_Index_Product::CT_BRAND] as $range): ?>
        <input type="checkbox" name="f[<?=FCom_IndexTank_Index_Product::CT_BRAND?>][]"
               value="<?=$range?>" onclick="this.form.submit()"
               <?=(in_array($range, $s['filter_selected'][FCom_IndexTank_Index_Product::CT_BRAND]))?'checked':''?>
               >  <?=$range?>
        (<?=$s['facets'][FCom_IndexTank_Index_Product::CT_BRAND][$range]?>)<br/>
<? endforeach ?>
        <br/>
    <label>Categories:</label><br/>
<? foreach ($s['filter'][FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX] as $cat_name => $cat_obj): ?>
    <?php for($i = 2; $i < strlen($cat_name); $i++) echo "+"; ?>
        <input type="checkbox" name="f[<?=FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX.$cat_name?>]"
               value="<?=$cat_obj->name?>" onclick="this.form.submit()"
               <?=(!empty($_GET['f'][FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX.$cat_name]))?'checked':''?>
               >
        <?=$cat_obj->name?> (<?=$cat_obj->count?>) <br/>
<? endforeach ?>
    </div>
</div>

</form>