<?php
    $m = $this->model;
    $hlp = FCom_Admin_Model_Role::i();
?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">Role Name</h4>
            <input type="text" id="model-role_name" name="model[role_name]" class="validate[required]" value="<?php echo $this->q($m->role_name) ?>"/>
        </li>
        <li>
            <h4 class="label">Permissions</h4>
            <div id="permissions"></div>
        </li>
    </ul>
</fieldset>
<script>
head(function() {
    $('#permissions').jstree({
        plugins: ['themes', 'json_data','ui','hotkeys','checkbox','search'],
        json_data: {
            data: <?php echo BUtil::toJson($m->getAllPermissionsTree()) ?>
        },
        checkbox: {
            override_ui:true,
            real_checkboxes:true,
            real_checkboxes_names: function(n) { return ['model[permissions]['+n.attr('path')+']', 1] }
        },
        search: { case_insensitive:true }
    }).bind('loaded.jstree', function(event, data) {
        var checked = <?php echo BUtil::toJson($m->getPermissionIds()) ?>;
        for (var id in checked) {
            data.inst.check_node('#'+id);
        }
    });
})
</script>