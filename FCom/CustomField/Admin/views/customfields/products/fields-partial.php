<?php
$m = $this->model;
?>
<input type="text" name="model[_fieldset_ids]" value="<?=$m->_fieldset_ids?>"/>
<input type="text" name="model[_add_field_ids]" value="<?=$m->_add_field_ids?>" id="cf_add_fields_ids"/>
<input type="text" name="model[_hide_field_ids]" value="<?=$m->_hide_field_ids?>" id="cf_hide_fields_ids"/>
<?php if(!empty($this->fields)):?>
    <?php foreach($this->fields as $field):?>
    <div id="cf_field_<?=$field->id?>">
        <h3><?=$field->frontend_label?></h3>


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
            <br/>
        <a href="javascript:void(0);" onclick="cf_field_remove(<?=$field->id?>)">remove</a>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
<pre>
<? print_r(BDb::many_as_array($this->fields)); ?>
<? print_r($m->as_array()) ?>
</pre>

<script type="text/javascript">
    function cf_field_remove(field_id)
    {
        var addfields = $('#cf_add_fields_ids').val();
        var addfield_ar=addfields.split(",");
        for(fid in addfield_ar){
            if(addfield_ar[fid] == field_id){
                addfield_ar.splice(fid,1);
            }
        }
        var addfields_new = addfield_ar.join(",");
        var replaced = false;
        if(addfields_new != addfields){
            replaced = true;
            $('#cf_add_fields_ids').val(addfields_new);
        }

        if(false == replaced){
            var hidefields = $('#cf_hide_fields_ids').val();
            if(hidefields){
                $('#cf_hide_fields_ids').val(hidefields + ',' + field_id);
            } else {
                $('#cf_hide_fields_ids').val(field_id);
            }
        }
        
        $.ajax({
            url: "/admin/customfields/products/field_remove?id=<?=$m->id?>&hide_field="+field_id
        }).done(function() {
            $('#cf_field_'+field_id).hide();
        });
    }
</script>