<?php
$s = $this->s;
?>
<?= BLocale::_("Categories"); ?>:<br/>
<a href="<?=BApp::href('indextank/search').'?'.BRequest::rawGet()?>"><?= BLocale::_("Any department"); ?></a><br/>
<?php foreach($s['available_categories'] as $data):?>
        <? foreach ($data as $obj):            ?>
            <?php if($obj->level) echo str_repeat("&nbsp;&nbsp;", $obj->level) ?>
                <? if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
                    <strong><?=$obj->name?> <?=$obj->show_count ? '('.$obj->count.')':''?></strong>
                <?php else:?>
                    <a href="<?=BApp::href($obj->url_path).'?'.BRequest::rawGet()?>"><?=$obj->name?>
                        <?=$obj->show_count ? '('.$obj->count.')':''?></a>
                <?php endif; ?>
                <br/>
        <? endforeach ?>
                <br/>
<?php endforeach; ?>