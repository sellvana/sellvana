<?php $m = $this->model ?>
<fieldset>
    <ul>
        <li><label for="main-node_name">Node Name</label>
            <input type="text" id="main-node_name" name="model[node_name]" value="<?php echo $this->q($m->node_name) ?>"></li>

        <li><label for="main-node_name">URL Key</label>
            <input type="text" id="main-url_key" name="model[url_key]" value="<?php echo $this->q($m->url_key) ?>"></li>

        <li><label for="main-node_type">Node Type</label><select id="main-node_type" name="model[node_type]">
            <?php echo $this->optionsHtml($this->node_types, $m->node_type) ?></select></li>

        <li><label for="main-reference">Reference</label>
            <input type="text" id="main-reference" name="model[reference]" value="<?php echo $this->q($m->reference) ?>"/></li>

        <li><label for="main-content">Content</label>
            <textarea id="main-content" name="model[content]"><?php echo $this->q($m->content) ?></textarea></li>

        <li><label for="main-layout_update">Layout Update</label>
            <textarea id="main-layout_update" name="model[layout_update]" style="width:90%; height:100px"><?php echo $this->q($m->layout_update) ?></textarea></li>

    </ul>
</fieldset>
<script>
head(function() {
    adminForm.wysiwygDestroy('main-content');
    adminForm.wysiwygCreate('main-content');
});
</script>