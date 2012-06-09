<?php
$s = $this->state;
?>


<div class="filters" style="border: 1px solid black; width: 200px;">

    <form id="product_list_pager" name="product_list_pager" autocomplete="off" method="get" action="">
    <br/>
        <br/>
        <?=$this->view('indextank/product/_pager_categories')->set('s', $s)?>
        <br/>

        <a href="<?=BApp::href('indextank/search').'?q='.$this->q(BRequest::i()->get('q'))?>">Clear filters</a>
        <br/>

<?php foreach($s['available_facets'] as $label => $data):?>
        <label><?=$label?>:</label><br/>
        <? foreach ($data as $obj): ?>
                <? if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
                    <a style="color:grey;" href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array($obj->param => ''))?>"><?=$obj->name?> (<?=$obj->count?>)</a>
                    <?php if(true == $s['save_filter']):?>
                        <input type="hidden" name="<?=$obj->param?>" value="<?=$obj->name?>" />
                    <?php endif; ?>
                <?php else:?>
                    <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array($obj->param => $obj->name))?>"><?=$obj->name?> (<?=$obj->count?>)</a>
                <?php endif; ?>
                <br/>
        <? endforeach ?>
                <br/>
<?php endforeach; ?>

    </form>

</div>

