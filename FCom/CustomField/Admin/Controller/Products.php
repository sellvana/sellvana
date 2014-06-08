<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CustomField_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    public function fieldsetsGridConfig()
    {
        $config = [
            'grid' => [
                'id'      => 'product_fieldsets',
                'caption' => 'Field Sets',
                'url' => $this->BApp->href('customfields/fieldsets/grid_data'),
                'orm' => 'FCom_CustomField_Model_SetField',
                'columns' => [
                    'id' => ['label' => 'ID', 'width' => 55, 'sorttype' => 'number', 'key' => true],
                    'set_code' => ['label' => 'Set Code', 'width' => 100, 'editable' => true],
                    'set_name' => ['label' => 'Set Name', 'width' => 200, 'editable' => true],
                    'num_fields' => ['label' => 'Fields', 'width' => 30],
                ],
                'actions' => [
                            'edit' => true,
                            'delete' => true
                ],
                'filters' => [
                            ['field' => 'set_name', 'type' => 'text'],
                            ['field' => 'set_code', 'type' => 'text'],
                            '_quick' => ['expr' => 'product_name like ? or set_code like ', 'args' =>  ['%?%', '%?%']]
                ]
            ]
        ];

        return $config;
    }

    public function variantFieldGridConfig($model)
    {
        $data = $model->getData('variants_fields');

        $config = [
            'config' => [
                'id' => 'variable-field-grid',
                'caption' => 'Variable Field Grid',
                'data_mode' => 'local',
                'data' => ($data === null ? [] : $data),
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true],
                    ['name' => 'name', 'label' => 'Field Name', 'width' => 300],
                    ['name' => 'field_code', 'label' => 'Field Code', 'width' => 300],
                    ['name' => 'frontend_label', 'label' => 'Frontend Label', 'width' => 300],
                    ['type' => 'btn_group',  'buttons' => [['name' => 'delete']]]
                ],
                'actions' => [
                                   'delete' => ['caption' => 'Remove']
                                ],
                'grid_before_create' => 'variantFieldGridRegister'
            ]
        ];

        return $config;
    }

    public function variantGridConfig($model)
    {
        $thumbUrl = $this->FCom_Core_Main->resizeUrl($this->BConfig->get('web/media_dir') . '/product/images', ['s' => 30]);
        $columns = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true, 'position' => 1]
        ];

        $vFields = $model->getData('variants_fields');
        if ($vFields !== null) {
            $pos = 2;
            foreach ($vFields as $f) {
                $f['options'] = $this->FCom_CustomField_Model_FieldOption->getListAssocById($f['id']);
                $f['label'] = $f['name'];
                $f['name'] = $f['field_code'];
                $f['field_id'] = $f['id'];
                $f['addable'] = true;
                $f['mass-editable'] = true;
                $f['width'] = 200;
                $f['position'] = $pos++;
                $f['validation'] = ['required' => true];
                $f['display'] = 'eval';
                $f['print'] = '"<p style=\"overflow:hidden\"><input type=\"hidden\" name=\''. $f['name'].'\' class=\"select-value-field required\" style=\"width: 170px\" /></p>"';
                $f['default'] = '';
                $columns[] = $f;
            }
        }
        $image = $this->variantImageGrid($model);
        $columns[] = ['type' => 'input', 'name' => 'variant_sku', 'label' => 'SKU', 'width' => 150, 'editable' => 'inline',
                        'addable' => true, 'default' => ''];
        $columns[] = ['type' => 'input', 'name' => 'variant_price', 'label' => 'PRICE', 'width' => 150, 'editable' => 'inline',
                        'addable' => true, 'validation' => ['number' => true], 'default' => ''];
        $columns[] = ['type' => 'input', 'name' => 'variant_qty', 'label' => 'QTY', 'width' => 150, 'editable' => 'inline',
                        'addable' => true, 'validation' => ['number' => true], 'default' => ''];
        $columns[] = ['name' => 'image', 'label' => 'IMAGES', 'width' => 250, 'display' => 'eval',
            'addable' => true, 'sortable' => false, 'print' => '"<input type=\"hidden\" class=\"store-variant-image-id\" value=\'"+ rc.row["variant_file_id"] +"\'/><ol class=\"dd-list columns dd-list-axis-x hide list-variant-image\"></ol><select class=\"form-control variant-image\"><option value></option></select>"' ];
        $columns[] = ['name' => 'variant_file_id',  'hidden' => true];
        $columns[] = ['name' => 'list_image',  'hidden' => true, 'default' => $image];
        $columns[] = ['name' => 'field_values',  'hidden' => true, 'default' => ''];
        $columns[] = ['name' => 'thumb_url',  'hidden' => true, 'default' => $thumbUrl];
        $columns[] = ['type' => 'btn_group',  'buttons' => [['name' => 'delete']] ];

        $data = [];

        $variants = $this->FCom_CustomField_Model_ProductVariant->orm()->where('product_id', $model->id)->find_many();
        if ($variants !== null) {
            foreach ($variants as $v) {
                $file_id = $v->getData('variant_file_id');
                $vField = [];
                $vField['field_values'] = $this->BUtil->objectToArray(json_decode($v->field_values));
                $vField['variant_sku'] = $v->variant_sku;
                $vField['variant_qty'] = $v->variant_qty;
                $vField['variant_price'] = $v->variant_price;
                $vField['variant_file_id'] = ($file_id)? $file_id: '';
                $vField['id'] = $v->id;
                $data[] = $vField;
            }
        }

        $config = [
            'config' => [
                'id' => 'variant-grid',
                'caption' => 'Variable Field Grid',
                'data_mode' => 'local',
                'data' => $data,
                'columns' => $columns,
                'filters' => [
                    '_quick' => ['expr' => 'field_name like ? or id like ', 'args' => ['%?%', '%?%']]
                ],
                'actions' => [
                    'new' => ['caption' => 'New Variant'],
                    'delete' => ['caption' => 'Remove']
                ],
                'grid_before_create' => 'variantGridRegister'
            ]
        ];

        return $config;

    }

    public function variantImageGrid($model)
    {
        $data = $this->BDb->many_as_array($model->mediaORM('I')
            ->left_outer_join('FCom_Catalog_Model_ProductMedia', ['pa.file_id', '=', 'pm.file_id'], 'pm')
            ->select(['pa.id', 'pa.position',  'a.file_name'])
            ->select('a.id', 'file_id')
            ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder')
            ->group_by('pa.id')
            ->find_many());
        return $data;
    }

    /**
     * @param $model FCom_Catalog_Model_Product
     * @return array
     */
    public function frontendFieldGrid($model)
    {
        $data = $model->getData('frontend_fields');
        if (!isset($data))
            $data = [];
        $config = [
            'config' => [
                'id' => 'frontend-field-grid',
                'caption' => 'Frontend Field Grid',
                'data_mode' => 'local',
                'data' => $data,
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true],
                    ['name' => 'name', 'label' => 'Field Name', 'width' => 200],
                    ['name' => 'label', 'label' => 'Field Label', 'width' => 200],
                    ['name' => 'input_type', 'label' => 'Input Type', 'width' => 200],
                    ['name' => 'options', 'label' => 'Options', 'width' => 200],
                    ['type' => 'input', 'name' => 'price', 'label' => 'Price', 'width' => 200, 'editable' => 'inline',
                        'validation' => ['number' => true]],
                    ['type' => 'btn_group', 'buttons' => [['name' => 'delete']]]
                ],
                'actions' => [
                    'add' => ['caption' => 'Add Fields'],
                    'delete' => ['caption' => 'Remove']
                ],
                'grid_before_create' => 'frontendFieldGridRegister'
            ]
        ];

        return $config;
    }

    public function formViewBefore()
    {
        $id = $this->BRequest->params('id', true);
        $p = $this->FCom_Catalog_Model_Product->load($id);

        if (!$p) {
            return;//$p = $this->FCom_Catalog_Model_Product->create();
        }

        $fields_options = [];
        $fields = $this->FCom_CustomField_Model_ProductField->productFields($p);
        foreach ($fields as $field) {
            $fields_options[$field->id] = $this->FCom_CustomField_Model_FieldOption->orm()
                ->where("field_id", $field->id)->find_many();
        }
        $view = $this->view('customfields/products/fields-partial');
        $view->set('model', $p)->set('fields', $fields)->set('fields_options', $fields_options);
    }

    public function action_field_remove()
    {
        $id = $this->BRequest->params('id', true);
        $p = $this->FCom_Catalog_Model_Product->load($id);
        if (!$p) {
            return;
        }
        $hide_field = $this->BRequest->params('hide_field', true);
        if (!$hide_field) {
            return;
        }
        $this->FCom_CustomField_Model_ProductField->removeField($p, $hide_field);
        $this->BResponse->json('');
    }

    public function action_fields_partial()
    {
        $id = $this->BRequest->params('id', true);
        $p = $this->FCom_Catalog_Model_Product->load($id);
        if (!$p) {
            $p = $this->FCom_Catalog_Model_Product->create();
        }

        $fields_options = [];
        $fields = $this->FCom_CustomField_Model_ProductField->productFields($p, $this->BRequest->request());
        foreach ($fields as $field) {
            $fields_options[$field->id] = $this->FCom_CustomField_Model_FieldOption->orm()
                ->where("field_id", $field->id)->find_many();
        }

        $view = $this->view('customfields/products/fields-partial');
        $view->set('model', $p)->set('fields', $fields)->set('fields_options', $fields_options);
        $this->BLayout->setRootView('customfields/products/fields-partial');
        $this->BResponse->render();
    }

    public function getInitialData($model)
    {
        $customFields = $model->getData('custom_fields');
        return !isset($customFields) ? -1 : $customFields;
    }
    public function fieldsetAry()
    {
        $sets = $this->BDb->many_as_array($this->FCom_CustomField_Model_Set->orm('s')->select('s.*')->find_many());

        return json_encode($sets);
    }

    public function fieldAry()
    {
        $fields = $this->BDb->many_as_array($this->FCom_CustomField_Model_SetField->orm('s')->select('s.*')->find_many());

        return json_encode($fields);
    }

    public function action_get_fieldset()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        $set = $this->FCom_CustomField_Model_Set->load($id);
        $fields = $this->BDb->many_as_array($this->FCom_CustomField_Model_SetField->orm('sf')
            ->join('FCom_CustomField_Model_Field', ['f.id', '=', 'sf.field_id'], 'f')
            ->select(['f.id', 'f.field_name', 'f.admin_input_type'])
            ->where('sf.set_id', $id)->find_many()
        );
        foreach ($fields as &$field) {
            if ($field['admin_input_type'] === 'select' ||  $field['admin_input_type'] === 'multiselect') {
                $field['options'] = $this->FCom_CustomField_Model_FieldOption->getListAssocById($field['id']);
            }
        }

        $this->BResponse->json(['id' => $set->id, 'set_name' => $set->set_name, 'fields' => ($fields)]);
    }

    public function action_get_field()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        $field = $this->FCom_CustomField_Model_Field->load($id);
        $options = $this->FCom_CustomField_Model_FieldOption->getListAssocById($field->id);
        $this->BResponse->json(['id' => $field->id, 'field_name' => $field->field_name,
            'admin_input_type' => $field->admin_input_type, 'multilang' => $field->multilanguage,
            'options' => $options, 'required' => $field->required]);
    }

    public function action_save__POST()
    {
         $data = $this->BRequest->post();
         $prodId = $data['id'];
         $json = $data['json'];

         $res = $this->BDb->many_as_array($this->FCom_CustomField_Model_ProductField->orm()->where('product_id', $prodId)->find_many());

         if (empty($res)) {
            $new = $this->FCom_CustomField_Model_ProductField->create();
            $new->product_id = $prodId;
            $new->_data_serialized = $json;
            $new->save();
            $status = 'Successfully saved.';
         } else {

            $row = $this->FCom_CustomField_Model_ProductField->load($res[0]['id']);
            $row->_data_serialized = $json;
            $row->save();
            $status = 'Successfully updated.';
         }

         $this->BResponse->json(['status' => $status]);
    }

    public function action_get_fields__POST()
    {
        $res = [];
        $data = $this->BRequest->post();
        $ids = explode(',', $data['ids']);
        $optionsModel = $this->FCom_CustomField_Model_FieldOption;
        $fieldModel = $this->FCom_CustomField_Model_Field;
        foreach ($ids as $id) {
            $field = $fieldModel->load($id);
            $options = join(',', array_keys($optionsModel->getListAssocById($id)));
            $res[] = ['id' => $id, 'name' => $field->field_name, 'label' => $field->frontend_label,
                'input_type' => $field->admin_input_type, 'options' => $options];
        }

        $this->BResponse->json($res);
    }

    public function getFieldTypes()
    {
        $f = $this->FCom_CustomField_Model_Field;
        return $f->fieldOptions('table_field_type');
    }

    public function getAdminInputTypes()
    {
        $f = $this->FCom_CustomField_Model_Field;
        return $f->fieldOptions('admin_input_type');
    }
}
