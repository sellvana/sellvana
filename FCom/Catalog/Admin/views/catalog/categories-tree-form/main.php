<?php $m = $this->model ?>
<fieldset>
    <ul class="form-list">
        <li class="label-l"><label for="main-node_name">Node Name</label>
            <input type="text" id="main-node_name" name="model[node_name]" value="<?php echo $this->q($m->node_name) ?>"></li>

        <li class="label-l"><label for="main-node_name">URL Key</label>
            <input type="text" id="main-url_key" name="model[url_key]" value="<?php echo $this->q($m->url_key) ?>"></li>

        <li class="label-l"><label for="main-node_name">Show in top menu</label>
            <input type="hidden" name="model[top_menu]" value="0">
            <input type="checkbox" name="model[top_menu]" value="1" <?=$m->top_menu == 1? 'checked':''?>></li>
    </ul>
</fieldset>