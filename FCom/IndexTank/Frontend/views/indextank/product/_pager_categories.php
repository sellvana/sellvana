<?php
$s = $this->s;
?>
<?= BLocale::_("Categories") ?>:<br/>
<a href="<?=BApp::href('catalog/search').'?'.BRequest::rawGet()?>"><?= BLocale::_("All categories") ?></a><br/>
<?php foreach($s['available_categories'] as $data):?>
    <?php foreach ($data as $obj):            ?>
        <div style="padding-left:<?=$obj->level*15?>px; white-space:nowrap;">
            <?php if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
                <strong><?=$obj->name?> <?=$obj->show_count ? '('.$obj->count.')':''?></strong>
            <?php else:?>
                <a href="<?=BApp::href($obj->url_path).'?'.BRequest::rawGet()?>"><?=$obj->name?>
                    <?=$obj->show_count && $obj->count ? '('.$obj->count.')':''?></a>
            <?php endif; ?>
        </div>
    <?php endforeach ?>
<br/>
<?php endforeach; ?>