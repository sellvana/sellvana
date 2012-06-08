<?php
$s = $this->s;
?>
Categories:<br/>
<a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array("f[category]" => ''))?>">Any department</a><br/>
<?php foreach($s['available_categories'] as $data):?>
        <? foreach ($data as $obj):            ?>
            <?php if($obj->level) echo str_repeat("&nbsp;&nbsp;", $obj->level) ?>
                <? if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
                    <input type="hidden" name="<?=$obj->param?>" value="<?=$obj->key.':'.$obj->name?>" />
                    <a style="color:grey;" href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array($obj->param => ''))?>"><?=$obj->name?> (<?=$obj->count?>)</a>
                <?php else:?>
                    <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array($obj->param => $obj->key.':'.$obj->name))?>"><?=$obj->name?> (<?=$obj->count?>)</a>
                <?php endif; ?>
                <br/>
        <? endforeach ?>
                <br/>
<?php endforeach; ?>