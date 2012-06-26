<?php if (!$this->mode || $this->mode==='view'): ?>

    <div class="adm-section-group">
		<div class="btns-set">
        	<button class="btn st2 sz2 btn-edit" onclick="return adminForm.tabAction('edit', this);"><span>Edit</span></button>
        </div>
        <ul class="form-list">
            <li>
                <h4 class="label">Attribute 1</h4>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vestibulum convallis varius. Donec et odio quis est blandit mattis.
            </li>
            <li>
                <h4 class="label">Attribute 2</h4>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vestibulum convallis varius. Donec et odio quis est blandit mattis. Aliquam ac nisl magna, sit amet vestibulum ipsum. Vestibulum ultrices justo sagittis ante interdum volutpat. Curabitur ullamcorper, neque pulvinar commodo gravida, augue tellus interdum nulla, a pulvinar leo nisi ac nisl. Nullam bibendum luctus sem, eget interdum leo blandit auctor. Integer ullamcorper tellus non justo ultrices tempor. Vivamus eu augue justo. Suspendisse ut neque nec neque ultrices aliquam dictum sed orci.
            </li>
            <li>
                <h4 class="label">Attribute 3</h4>
            </li>
        </ul>
    </div>

<?php elseif ($this->mode==='create' || $this->mode==='edit'): ?>

    <fieldset class="adm-section-group">

<style>
#pager-product_fieldsets .ui-pg-table,
#pager-product_fields .ui-pg-table { table-layout:auto !important; }
</style>
        <table width="90%">
            <tr>
                <td width="50%">
<?=$this->view('jqgrid')->set('config', FCom_CustomField_Admin_Controller_Products::i()->fieldsetsGridConfig()) ?>
                </td>
                <td width="50%">
<?=$this->view('jqgrid')->set('config', FCom_CustomField_Admin_Controller_Products::i()->fieldsGridConfig()) ?>
                </td>
            </tr>
        </table>
        <div id="custom-fields-partial" data-src="<?=BApp::href('customfields/products/fields_partial/?id='.$this->model->id)?>">
            <?=$this->view('customfields/products/fields-partial')->set('model', $this->model)?>
        </div>
    </fieldset>
<script>
function addCustomFieldSets() {
    var sel = $(this).jqGrid('getGridParam', 'selarrrow');
    console.log(sel);
    partial('#custom-fields-partial', {params:{add_fieldset_ids:sel}});
}
function addCustomFields() {
    var sel = $(this).jqGrid('getGridParam', 'selarrrow');
    console.log(sel);
    partial('#custom-fields-partial', {params:{add_field_ids:sel}});
}
</script>
<?php endif ?>