<?php $m = $this->model; ?>
<input type="hidden" name="file" value="<?=$m->id;?>" />
<fieldset class="adm-section-group">
    <ul class="form-list">

        <li>
            <h4 class="label">Source </h4>
            <textarea name="source" style="width: 600px; height: 200px;" ><?php echo $this->q($m->source) ?></textarea>
        </li>


    </ul>

</fieldset>
<script>
head(function() {
adminForm.wysiwygCreate('main-content');
});
</script>