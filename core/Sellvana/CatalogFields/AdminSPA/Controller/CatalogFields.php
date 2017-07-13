<?php

/**
 * Class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields
 *
 * @property FCom_Core_Model_Field FCom_Core_Model_Field
 * @property FCom_Core_Model_FieldOption FCom_Core_Model_FieldOption
 */

class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        $fld = $this->FCom_Core_Model_Field;

        $yesNoOpts = ['0' => 'No', '1' => 'Yes'];

        return [
            'id' => 'catalog_fields',
            'data_url' => 'catalogfields/grid_data',
            'columns' => [
                ['type' => 'row-select'],
                ['type' => 'actions', 'actions' => [
                    ['type' => 'edit', 'link' => 'fields/form?id={id}'],
                    ['type' => 'delete', 'delete_url' => true],
                ]],
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'field_code', 'label' => 'Field Code',
                 'datacell_template' => '<td><a :href="\'#/catalog/fields/form?id=\'+row.id">{{row.field_code}}</a></td>'],
                ['name' => 'field_name', 'label' => 'Field Name',
                 'datacell_template' => '<td><a :href="\'#/catalog/fields/form?id=\'+row.id">{{row.field_name}}</a></td>'],
                ['name' => 'field_type', 'label' => 'Field Type'],
                ['name' => 'frontend_label', 'label' => 'Frontend Label'],
                ['name' => 'frontend_show', 'label' => 'Show on frontend',
                 'options' => $fld->fieldOptions('frontend_show')],
                ['name' => 'sort_order', 'label' => 'Sort Order'],
                ['name' => 'table_field_type', 'label' => 'DB Type', 'options' => $fld->fieldOptions('table_field_type')],
                ['name' => 'admin_input_type', 'label' => 'Input Type',
                 'options' => $fld->fieldOptions('admin_input_type')],
                ['name' => 'num_options', 'label' => 'Options', 'default' => 0],
                ['name' => 'system', 'label' => 'System field', 'options' => $yesNoOpts],
                ['name' => 'multilanguage', 'label' => 'Multi language', 'options' => $yesNoOpts],
                ['name' => 'swatch_type', 'label' => 'Swatch type', 'options' => $fld->fieldOptions('swatch_type')],
                ['name' => 'required', 'label' => 'Required', 'options' => $yesNoOpts],
            ],
            'filters' => [
                ['field' => 'id', 'type' => 'number'],
                ['field' => 'field_code', 'type' => 'text'],
                ['field' => 'field_name', 'type' => 'text'],
                ['field' => 'frontend_label', 'type' => 'text'],
                ['field' => 'frontend_show', 'type' => 'multiselect'],
                ['field' => 'table_field_type', 'type' => 'multiselect'],
                ['field' => 'admin_input_type', 'type' => 'multiselect'],
                ['field' => 'num_options', 'type' => 'text'],
                ['field' => 'system', 'type' => 'multiselect'],
                ['field' => 'multilanguage', 'type' => 'multiselect'],
                ['field' => 'swatch_type', 'type' => 'multiselect'],
                ['field' => 'required', 'type' => 'multiselect'],
            ],
            'export' => true,
            'pager' => true,
        ];
    }

    public function getGridOrm()
    {
        $subSql = '(select count(*) from ' . $this->FCom_Core_Model_FieldOption->table() . ' where field_id=f.id)';
                                              ;
        return $this->FCom_Core_Model_Field
            ->orm('f')
            ->where('field_type', 'product')
            ->select('f.*')
            ->select($subSql, 'num_options');
    }

    public function getFormData()
    {
        $pId = $this->BRequest->get('id');
        $bool = [0 => 'no', 1 => 'Yes'];

        $field = $this->FCom_Core_Model_Field->load($pId);
        if (!$field) {
            throw new BException('Field not found');
        }

        $result = [];

        $result['form']['field'] = $field->as_array();

        $result['form']['config']['actions'] = true;
        $result['form']['config']['title'] = $field->get('field_name');

        $result['form']['config']['tabs'] = '/catalog/fields/form';
        $result['form']['config']['default_field'] = ['model' => 'field', 'tab' => 'main'];
        $result['form']['config']['fields'] = [
            ['name' => 'field_code', 'label' => 'Field Code'],
            ['name' => 'field_name', 'label' => 'Field Name'],
        ];

        $result['form']['i18n'] = 'field';

        return $result;
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            $r = $this->BRequest;
            $data = $r->post();
            $id = $r->param('id', true);
            $model = $this->FCom_Core_Model_Field->load($id);
            if (!$model) {
                throw new BException("This field does not exist");
            }

            if ($data) {
                $model->set($data);
            }

            $origModelData = $modelData = $model->as_array();
            $validated = $model->validate($modelData, [], 'product');
            //if ($modelData !== $origModelData) {
            //    var_dump($modelData);
            //    $model->set($modelData);
            //}


            if ($validated) {
                $model->save();
                $result = $this->getFormData();
                $result['form'] = $this->normalizeFormConfig($result['form']);
                $this->ok()->addMessage('Inventory was saved successfully', 'success');
            } else {
                $result = ['status' => 'error'];
                $this->error()->addMessage('Cannot save data, please fix above errors', 'error');
            }

        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }
}
