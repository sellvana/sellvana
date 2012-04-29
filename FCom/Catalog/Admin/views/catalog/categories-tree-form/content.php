<?php $m = $this->model ?>
<fieldset>
    <ul class="form-list">
        <li class="label-top"><label for="main-content">Content</label>
            <textarea id="main-content" name="model[content]"><?php echo $this->q($m->content) ?></textarea></li>

        <li class="label-top"><label for="main-layout_update">Layout Update</label>
            <textarea id="main-layout_update" name="model[layout_update]" style="width:90%; height:100px"><?php echo $this->q($m->layout_update) ?></textarea></li>

    </ul>
</fieldset>
<script>
head(function() {
    var ck = CKEDITOR.instances['main-content'];
    ck && ck.destroy();
    $('#main-content').ckeditor();
});
</script>