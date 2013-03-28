<?php
$facets = $this->products_data['facets'];
$s = $this->products_data['state'];
$hlp = FCom_CatalogIndex::i();
?>
<section class="block block-filter">
    <header class="block-title"><span class="title">Narrow Results</span></header>
    <form class="block-content" method="get" action="">
        <?php if (!empty($facets)): ?>
            <?php foreach ($facets as $fKey => $facet): ?>
                <?php if (!empty($facet['custom_view'])): ?>
                    <?=$this->view($facet['custom_view'])->set('facet_key', $fKey)->set('facet', $facet)
                            ->set('products_data', $this->products_data) ?>
                <?php elseif (!empty($facet['values'])): ?>
                    <section class="block-sub block-attribute">
                        <header class="block-sub-title"><span class="title"><?=$facet['display']?></header>
                        <ul>
                            <?php foreach ($facet['values'] as $vKey => $value): ?>
                                <?php if (!empty($value['selected'])): ?>
                                    <li><a class="active" href="<?= $hlp->getUrl(null, array($fKey => $vKey)) ?>">
                                        <span class="icon"></span><?=$value['display']?></a></li>
                                    <?php if (!empty($s['save_filter'])): ?>
                                        <input type="hidden" name="<?= $fKey ?>" value="<?= $vKey ?>"/>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <li><a href="<?= $hlp->getUrl(array($fKey => $vKey)) ?>">
                                         <span class="icon"></span><?=$value['display']?>
                                         <?php if (!empty($value['cnt'])):?><span class="count">(<?=$value['cnt']?>)</span><?php endif ?>
                                         </a></li>
                                <?php endif; ?>
                            <?php endforeach ?>
                        </ul>
                    </section>
                <?php endif ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </form>
</section>

