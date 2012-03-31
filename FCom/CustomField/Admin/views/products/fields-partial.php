<?php
$m = $this->model;
?>
<input type="text" name="model[_fieldset_ids]" value="<?=$m->_fieldset_ids?>"/>
<input type="text" name="model[_add_field_ids]" value="<?=$m->_add_field_ids?>"/>
<input type="text" name="model[_hide_field_ids]" value="<?=$m->_hide_field_ids?>"/>
<pre>
<? print_r(BDb::many_as_array($this->fields)); ?>
<? print_r($m->as_array()) ?>
</pre>