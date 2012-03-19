<?php
    $m = $this->model;
?>

<?php if (!$this->mode || $this->mode==='view'): ?>

    <div class="adm-section-group">
        <div class="btns-set">
        	<button class="btn st2 sz2 btn-edit" onclick="return adminForm.tabAction('edit', this);"><span>Edit</span></button>
       	</div>
        <ul class="form-list">
            <li>
                <h4 class="label">Short Description</h4>
                <?php echo $this->q($m->short_description) ?>
            </li>
            <li>
                <h4 class="label">Long Description</h4>
                <?php echo $m->description ?>
            </li>
            <li>
                <h4 class="label">Unit of Measures</h4>
            </li>
        </ul>
    </div>

<script>
head(function() {
    adminForm.wysiwygDestroy('general-info-description');
});
</script>

<?php elseif ($this->mode==='create' || $this->mode==='edit'): ?>

    <fieldset class="adm-section-group">
<?php if ($this->mode!=='create'): ?>
        <div class="btns-set">
            <button class="btn st3 sz2 btn-cancel" onclick="return adminForm.tabAction('cancel', this);"><span>Cancel</span></button>
            <button class="btn st1 sz2 btn-save" onclick="return adminForm.tabAction('save', this);"><span>Save</span></button>
        </div>
<?php endif ?>
        <ul class="form-list">
            <li>
                <h4 class="label">Short Description</h4>
                <input type="text" name="model[short_description]" value="<?php echo $this->q($m->short_description) ?>"/>
            </li>
            <li>
                <h4 class="label">Long Description</h4>

                <textarea id="general-info-description" name="model[description]"><?php echo $this->q($m->description) ?></textarea>
            </li>
            <li>
                <h4 class="label">Unit of Measures</h4>
            </li>
        </ul>
    </fieldset>
    <script>
head(function() {
    adminForm.wysiwygCreate('general-info-description');
});
    </script>

<?php endif ?>

<?php echo $this->hook('catalog/products/tab/main', array('model'=>$this->model)); ?>