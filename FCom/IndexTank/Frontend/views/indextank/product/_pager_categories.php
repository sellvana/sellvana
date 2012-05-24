<?php
$s = $this->s;
?>
Categories:<br/>
<a href="<?=BUtil::setUrlQuery(BUtil::getCurrentUrl(), array("f[category]" => ''))?>">Any department</a><br/>
<?php foreach($s['available_facets'] as $label => $data):
    if('Categories' != $label){
                continue;
    }
    ?>
        <? foreach ($data as $obj):            ?>
            <?php if($obj->level) echo str_repeat("&nbsp;&nbsp;", $obj->level) ?>
                <? if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
                    <a style="color:grey;" href="<?=BUtil::setUrlQuery(BUtil::getCurrentUrl(), array($obj->param => ''))?>"><?=$obj->name?> (<?=$obj->count?>)</a>
                <?php else:?>
                    <a href="<?=BUtil::setUrlQuery(BUtil::getCurrentUrl(), array($obj->param => $obj->key.':'.$obj->name))?>"><?=$obj->name?> (<?=$obj->count?>)</a>
                <?php endif; ?>
                <br/>
        <? endforeach ?>
                <br/>
<?php endforeach; ?>