<?php
$s = $this->state;
?>
<section class="block block-filter">
	<header class="block-title"><span class="title">Narrow Results</span></header>
    <form class="block-content" method="get" action="">
        <?=$this->view('catalogindex/product/_pager_categories')->set('s', $s)?>
    <?php if (!empty($s['available_facets'])): ?>
        <?php foreach($s['available_facets'] as $label => $data):?>
            <section class="block-sub block-attribute">
                            <header class="block-sub-title"><span class="title"><?=$label?></header>
                            <ul>
                    <?php foreach ($data as $obj): ?>
                        <?php if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
                            <li><a class="active" href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array($obj->param => ''))?>"><span class="icon"></span><?=$obj->name?> <span class="count">(<?=$obj->count?>)</span></a></li>
                            <?php if(true == $s['save_filter']):?>
                                <input type="hidden" name="<?=$obj->param?>" value="<?=$obj->name?>" />
                            <?php endif; ?>
                        <?php else:?>
                            <li><a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array($obj->param => $obj->name))?>"><span class="icon"></span><?=$obj->name?> <span class="count">(<?=$obj->count?>)</span></a></li>
                        <?php endif; ?>
                    <?php endforeach ?>
                    </ul>
                    </section>
        <?php endforeach; ?>
    <?php endif; ?>
    </form>
</section>

