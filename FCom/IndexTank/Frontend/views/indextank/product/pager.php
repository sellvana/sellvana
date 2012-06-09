<?php
$s = $this->state;
if(empty($s['p'])) $s['p'] = 0;
//$price_ranges = $this->price_ranges;

$psOptions = array(2, 25, 50, 100, 500, 30000);
$sortOptions = $this->sort_options ? $this->sort_options : array(
    '' => 'Sort...',
    'relevance' => 'Relevance',
    'base_price_asc' => 'Price (Lower first)',
    'base_price_desc' => 'Price (Higher first)',
);

?>


<div style="border: 1px solid black">

    <form autocomplete="off" method="get" action="">
        <?php foreach($s['available_facets'] as $label => $data):?>
        <? foreach ($data as $obj): ?>
                <? if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
                    <input type="hidden" name="<?=$obj->param?>" value="<?=$obj->name?>" />
                <?php endif; ?>
        <? endforeach ?>
    <?php endforeach; ?>

    <div class="rows f-left">
        <strong class="count"><?=!empty($s['c'])?$s['c']:0?> found.</strong>
        <input type="text" name="q" id="query" autocomplete="off" value="<?=$this->q(BRequest::i()->get('q'))?>"/>
        <input type="submit" value="Search">
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

                    <br/>
    <div class="pages">
    <label>Page:</label>
    <? if ($s['p']>1): ?>
        <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('p' => $s['p']-1))?>" class="arrow-left" >&lt;</a>
    <? endif ?>
        <?=$s['p']?> of <?=$s['mp']?>
    <? if ($s['p']<$s['mp']): ?>
        <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('p' => $s['p']+1))?>" class="arrow-right" >&gt;</a>
    <? endif ?>

	</div>
    </form>
</div>

