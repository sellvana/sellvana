<?php
$m = $this->model;
?>
<input type="text" name="model[_fieldset_ids]" value="<?=$m->_fieldset_ids?>"/>
<input type="text" name="model[_add_field_ids]" value="<?=$m->_add_field_ids?>"/>
<input type="text" name="model[_hide_field_ids]" value="<?=$m->_hide_field_ids?>"/>
<?php if(!empty($this->fields)):?>
    <?php foreach($this->fields as $field):?>
        <?php if($field->admin_input_type == 'select'):?>
            <legend><?=$field->frontend_label?></legend>
            <select name="model[<?= $field->field_code ?>]">
                <?php foreach($this->fields_options[$field->id] as $field_option):?>
                    <option value="<?=$field_option->label?>"><?=$field_option->label?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
<pre>
<? print_r(BDb::many_as_array($this->fields)); ?>
<? print_r($m->as_array()) ?>
</pre>