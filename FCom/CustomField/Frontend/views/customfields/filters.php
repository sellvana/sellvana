<?php if (!empty($this->selected_filters)):?>
    <section class="block-sub">
            <header class="block-sub-title"><span class="title"><?= BLocale::_("Selected filters") ?></span></header>
            <a href="<?=BRequest::baseUrl().BRequest::rawPath()?>"><?= BLocale::_("Clear filters") ?></a> <br/>
    <?php foreach($this->selected_filters as $label => $filterGroup):?>
        <b><?=$label?></b>
        <ul>
            <?php foreach($filterGroup as $filter):?>
                <?php if(is_array($filter['value'])):?>
                    <?php foreach($filter['value'] as $fval):?>
                        <li><a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('f['.$filter['key'].']['.$fval.']' => ''))?>"> - <?=$fval?></a></li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li><a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('f['.$filter['key'].']' => ''))?>"> - <?=$filter['value']?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>

    <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if (!empty($this->groups)):?>
    <section class="block-sub">
            <header class="block-sub-title"><span class="title"><?= BLocale::_("Filters") ?></span></header>
    <?php foreach($this->groups as $label => $groupValues):?>
        <b><?=$label?></b>
        <ul>
            <?php foreach($groupValues['values'] as $gv):?>
                <?php if('Inclusive' == $groupValues['facet_select']):?>
                    <li><a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('f['.$groupValues['key'].']['.$gv.']' => $gv))?>"><?=$gv?></a></li>
                <?php else: ?>
                    <li><a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('f['.$groupValues['key'].']' => $gv))?>"><?=$gv?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>

    <?php endforeach; ?>
    </section>
<?php endif; ?>