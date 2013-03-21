<?php
$s = $this->pager_view->state;
$filters = $this->pager_view->filters;
if (!$filters) $filters = array();
?>
<?php if (!empty($filters)):?>
    <?php foreach($filters as $fkey => $fval):?>
        <?php if (is_array($fval)):?>
            <?php foreach($fval as $fvalsingle):?>
                <input type="hidden" name="f[<?=$fkey?>][<?=$fvalsingle?>]" value="<?=$fvalsingle?>" />
            <?php endforeach; ?>
        <?php else:?>
            <input type="hidden" name="f[<?=$fkey?>]" value="<?=$fval?>" />
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($s['available_facets'])): ?>
    <?php foreach($s['available_facets'] as $label => $data):?>
        <?php foreach ($data as $obj): ?>
            <?php if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
                <input type="hidden" name="<?=$obj->param?>" value="<?=$obj->name?>" />
            <?php endif; ?>
        <?php endforeach ?>
    <?php endforeach; ?>
<?php endif; ?>