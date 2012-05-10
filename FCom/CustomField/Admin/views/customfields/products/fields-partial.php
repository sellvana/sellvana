<?php
$m = $this->model;
?>
<input type="text" name="model[_fieldset_ids]" value="<?=$m->_fieldset_ids?>"/>
<input type="text" name="model[_add_field_ids]" value="<?=$m->_add_field_ids?>"/>
<input type="text" name="model[_hide_field_ids]" value="<?=$m->_hide_field_ids?>"/>
<?php if(!empty($this->fields)):?>
    <?php foreach($this->fields as $field):?>
        <legend><?=$field->frontend_label?></legend>


        <?php if($field->admin_input_type == 'select'):?>
            <select name="model[<?= $field->field_code ?>]">
                <?php foreach($this->fields_options[$field->id] as $field_option):?>
                    <option value="<?=$field_option->label?>"
                        <?= ($field_option->label == $m->{$field->field_code})?'selected':''?> ><?=$field_option->label?></option>
                <?php endforeach; ?>
            </select>


        <?php elseif($field->admin_input_type == 'text'):?>
            <?php if($field->table_field_type == 'date' || $field->table_field_type == 'datetime'):?>
                <div class="datepicker_wrapper">
                    <input type="text" name="model[<?= $field->field_code ?>]" value="<?=$m->{$field->field_code}?>" id="datepicker" />
                </div>
                <script>
                    $(function() {
                        $( "#datepicker" ).datepicker({ dateFormat: "yy-mm-dd", constrainInput: false });
                    });
                </script>
            <?php else: ?>
                <input type="text" name="model[<?= $field->field_code ?>]" value="<?=$m->{$field->field_code}?>" />
            <?php endif; ?>


        <?php elseif($field->admin_input_type == 'textarea'):?>
            <textarea name="model[<?= $field->field_code ?>]" ><?=$m->{$field->field_code}?></textarea>


        <?php elseif($field->admin_input_type == 'boolean'):?>
            <input type="hidden" name="model[<?= $field->field_code ?>]" value="0" />
            <input type="checkbox" name="model[<?= $field->field_code ?>]" value="1"
                <?= (!empty($m->{$field->field_code}))?'checked':''?> />
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
<pre>
<? print_r(BDb::many_as_array($this->fields)); ?>
<? print_r($m->as_array()) ?>
</pre>