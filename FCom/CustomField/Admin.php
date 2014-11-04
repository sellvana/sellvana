<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CustomField_Admin extends BClass
{
/*
    public function productAfterSave($args)
    {
        $p = $args['model'];
        $data = $p->as_array();
        $fields = $this->FCom_CustomField_Model_Field->fieldsInfo('product', true);
        if (array_intersect($fields, array_keys($data))) {
            $custom = $this->FCom_CustomField_Model_ProductField->load($p->id, 'product_id');
            if (!$custom) {
                $custom = $this->FCom_CustomField_Model_ProductField->create();
            }
            $custom->set('product_id', $p->id)->set($data)->save();
        }
        // not deleting to preserve meta info about fields
    }
*/
    public function onProductGridColumns($args)
    {
        $fields = $this->FCom_CustomField_Model_Field->orm('f')->find_many();
        foreach ($fields as $f) {
            $col = ['label' => $f->field_name, 'index' => 'pcf.' . $f->field_name, 'hidden' => true];
            if ($f->admin_input_type == 'select') {
                $col['options'] = $this->FCom_CustomField_Model_FieldOption->orm()
                    ->where('field_id', $f->id)
                    ->find_many_assoc(stripos($f->table_field_type, 'varchar') === 0 ? 'label' : 'id', 'label');
            }
            $args['columns'][$f->field_code] = $col;
        }
    }

    public function onProductsFormViewBefore()
    {
        $id = $this->BRequest->param('id', true);
        $p = $this->BLayout->view('admin/form')->get('model');
        #$p = $this->FCom_Catalog_Model_Product->load($id);

        if (!$p) {
            return;//$p = $this->FCom_Catalog_Model_Product->create();
        }

        $fieldsOptions = [];
        $fields = $this->FCom_CustomField_Model_ProductField->productFields($p);
        if ($fields) {
            $fieldIds = $this->BUtil->arrayToOptions($fields, 'id');
            $fieldOptionsAll = $this->FCom_CustomField_Model_FieldOption->orm()->where_in("field_id", $fieldIds)
                ->order_by_asc('field_id')->order_by_asc('label')->find_many();
            foreach ($fieldOptionsAll as $option) {
                $fieldsOptions[$option->get('field_id')][] = $option;
            }
        }
        $view = $this->BLayout->view('customfields/products/fields-partial');
        $view->set('model', $p)->set('fields', $fields)->set('fields_options', $fieldsOptions);
    }

    public function onProductFormPostAfterValidate($args)
    {
        $model = $args['model'];
        $data = $args['data'];

        if (!empty($data['custom_fields'])) {
            $model->setData('custom_fields', $data['custom_fields']);
        }

        $hlp = $this->FCom_CustomField_Model_ProductVariant;
        if (!empty($data['vfields'])) {
            $modelFieldOption = $this->FCom_CustomField_Model_FieldOption;
            $vfields = json_decode($data['vfields'], true);
            foreach ($vfields as $f) {
                $op = $this->FCom_CustomField_Model_FieldOption->getListAssocById($f['id']);
                $arr_diff = array_diff($f['options'], $op);
                foreach($arr_diff as $val) {
                    $modelFieldOption->create(['field_id' => $f['id'], 'label' => $val])->save();
                }
            }

            $model->setData('variants_fields', json_decode($data['vfields'], true));
        }
        if (isset($data['variants'])) {
            $variantsData = array();
            if ($data['variants'] != '') {
                $variantsData = $this->BUtil->objectToArray(json_decode($data['variants']));
            }
            $hlp->delete_many(['product_id'=> $model->id()]);
            if (count($variantsData) > 0) {
                $variantIds = $this->BUtil->arrayToOptions($variantsData, 'id');
                $variants = $hlp->orm()->where_in('id', $variantIds)->find_many_assoc();
                foreach($variantsData as $arr) {
                    $data = [
                        'product_id' => $model->id(),
                        'variant_sku' => $arr['variant_sku'],
                        'variant_price' => $arr['variant_price'],
                        'variant_qty' => $arr['variant_qty'],
                        'field_values' => json_encode($arr['field_values']),
                        'data_serialized' => json_encode(['variant_file_id' => $arr['variant_file_id']]),
                    ];
                    if (!empty($variants[$arr['id']])) {
                        $variants[$arr['id']]->set($data)->save();
                    } else {
                        $hlp->create($data)->save();
                    }
                }
            }
//            $model->setData('variants', json_decode($data['variants'], true));
        }

        if (!empty($data['prod_frontend_data'])) {
            $model->setData('frontend_fields', json_decode($data['prod_frontend_data'], true));
        }
    }
}
