<?php
$fKey = $this->facet_key;
$facet = $this->facet;
$facets = $this->products_data['facets'];
$s = $this->products_data['state'];
?>
<section class="block-sub">
    <?php if (!empty($facet['values'])): ?>
        <header class="block-sub-title"><span class="title"><?= BLocale::_("Categories") ?></span></header>
		<ul>
	    <?php foreach ($facet['values'] as $vKey=>$value): ?>
	        <li style="padding-left:<?=$value['level']*10?>px;">
                <?php if (!empty($value['selected'])): ?>
	                <strong><?=$value['display']?></strong>
                <?php elseif (!empty($value['parent'])): ?>
                    <strong><a href="<?=BApp::href($vKey).'?'.BRequest::rawGet()?>"><?=$value['display']?></a></strong>
	            <?php else: ?>
	                <a href="<?=BApp::href($vKey).'?'.BRequest::rawGet()?>"><?=$value['display']?>
	                    <?=$value['cnt'] ? '<span class="count">('.$value['cnt'].')</span>':''?></a>
	            <?php endif; ?>
	        </li>
	    <?php endforeach ?>
	    </ul>
    <?php endif; ?>
    <a href="<?=BApp::href('catalogindex/search').'?q='.$this->q(BRequest::i()->get('q'))?>"><?= BLocale::_("Clear filters") ?></a>
</section>