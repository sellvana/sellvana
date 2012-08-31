<?php
$s = $this->s;
?>
<section class="block-sub">
	<header class="block-sub-title"><span class="title"><?= BLocale::_("Categories") ?></span></header>
	<a href="<?=BApp::href('catalog/search').'?'.BRequest::rawGet()?>"><?= BLocale::_("All categories") ?></a>
	<?php foreach($s['available_categories'] as $data):?>
		<ul>
	    <?php foreach ($data as $obj):            ?>
	        <li style="padding-left:<?=$obj->level*20?>px;">
	            <?php if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
	                <strong><?=$obj->name?> <?=$obj->show_count ? '<span class="count">('.$obj->count.')</span>':''?></strong>
	            <?php else:?>
	                <a href="<?=BApp::href($obj->url_path).'?'.BRequest::rawGet()?>"><?=$obj->name?>
	                    <?=$obj->show_count && $obj->count ? '<span class="count">('.$obj->count.')</span>':''?></a>
	            <?php endif; ?>
	        </li>
	    <?php endforeach ?>
	    </ul>
	<?php endforeach; ?>
        <a href="<?=BApp::href('indextank/search').'?q='.$this->q(BRequest::i()->get('q'))?>"><?= BLocale::_("Clear filters") ?></a>
</section>