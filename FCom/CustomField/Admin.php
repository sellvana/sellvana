<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_CustomField_Admin
 *
 * @property FCom_CustomField_Model_Field $FCom_CustomField_Model_Field
 * @property FCom_CustomField_Model_FieldOption $FCom_CustomField_Model_FieldOption
 * @property FCom_CustomField_Model_ProductField $FCom_CustomField_Model_ProductField
 * @property FCom_CustomField_Model_ProductVarfield $FCom_CustomField_Model_ProductVarfield
 * @property FCom_CustomField_Model_ProductVariant $FCom_CustomField_Model_ProductVariant
 * @property FCom_CustomField_Model_ProductVariantField $FCom_CustomField_Model_ProductVariantField
 * @property FCom_CustomField_Model_ProductVariantImage $FCom_CustomField_Model_ProductVariantImage
 */
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
        /** @var FCom_CustomField_Model_Field[] $fields */
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

        echo "<pre>"; var_dump($data); exit;
        if (!empty($data['custom_fields'])) {
            $model->setData('custom_fields', $data['custom_fields']);
        }

        if (!empty($data['prod_frontend_data'])) {
            $model->setData('frontend_fields', $this->BUtil->fromJson($data['prod_frontend_data']));
        }

        // get new variant fields data from form
        $varFieldsData = [];
        if (!empty($data['vfields'])) {
            $varFieldsPost = $this->BUtil->fromJson($data['vfields']);
            foreach ($varFieldsPost as $vf) {
                $varFieldsData[$vf['id']] = $vf;
            }
        }

        $pId = $model->id();
        
        // retrieve variant fields already associated to product
        $prodVarfieldHlp = $this->FCom_CustomField_Model_ProductVarfield;
        $prodVarfieldModels = $prodVarfieldHlp->orm()->where('product_id', $pId)->find_many_assoc('field_id');

        if ($varFieldsData || $prodVarfieldModels) {
            // retrieve custom fields
            $fieldHlp = $this->FCom_CustomField_Model_Field;
            $fieldModels = $fieldHlp->orm()->where_in('id', array_keys($varFieldsData))->find_many_assoc();

            // retrieve related custom fields options
            $fieldOptionHlp = $this->FCom_CustomField_Model_FieldOption;
            $fieldOptionsModels = $fieldOptionHlp->orm()->where_in('field_id', array_keys($varFieldsData))->find_many();
            $fieldOptionsById = [];
            $fieldOptionsByLabel = [];
            foreach ($fieldOptionsModels as $m) {
                $fieldOptionsById[$m->get('field_id')][$m->id()] = $m->get('label');
                $fieldOptionsByLabel[$m->get('field_id')][$m->get('label')] = $m->id();
            }

            // retrieve product variant models
            $prodVariantHlp = $this->FCom_CustomField_Model_ProductVariant;
            $prodVariantModels = $prodVariantHlp->orm()->where('product_id', $pId)->find_many_assoc();
            
            // retrieve related product variants field values and associate with variants
            $prodVariantFieldHlp = $this->FCom_CustomField_Model_ProductVariantField;
            $prodVariantFieldModels = $prodVariantFieldHlp->orm()->where('product_id', $pId)->find_many_assoc('id');
            $prodVariantFieldsArr = [];
            foreach ($prodVariantFieldModels as $m) {
                $f = $fieldModels[$m->get('field_id')];
                $v = $fieldOptionsById[$m->get('field_id')][$m->get('option_id')];
                // TODO: implement locates for field option labels
                $prodVariantFieldsArr[$m->get('variant_id')][$f->get('field_code')] = $v->get('label');
            }

            // retrieve related product variants images
            $prodVariantImageHlp = $this->FCom_CustomField_Model_ProductVariantImage;
            $prodVariantImageModels = $prodVariantImageHlp->orm()->where('product_id', $pId)->find_many();
            $prodVariantImages = [];
            foreach ($prodVariantImageModels as $m) {
                $prodVariantImages[$m->get('variant_id')][$m->get('file_id')] = $m->get('position');
            }


            // update or create product variant fields
            $pos = 0;
            foreach ($varFieldsData as $vfId => $vf) {
                if (empty($prodVarfields[$vfId])) {
                    $prodVarfields[$vfId] = $prodVarfieldHlp->create(['product_id' => $pId, 'field_id' => $vfId]);
                }
                $prodVarfields[$vfId]->set(['field_label' => $vf['name'], 'position' => $pos])->save();
                $pos++;
            }
            // delete product variant fields if needed
            foreach ($prodVarfieldModels as $m) {
                if (empty($varFieldsData[$m->id()])) {
                    $m->delete();
                }
            }

            $prodVarHlp = $this->FCom_CustomField_Model_ProductVariant;
            $variantsData = array();
            if ($data['variants'] != '') {
                $variantsData = $this->BUtil->objectToArray(json_decode($data['variants']));
            }
            $hlp->delete_many(['product_id'=> $pId]);
            if (count($variantsData) > 0) {
                $variantIds = $this->BUtil->arrayToOptions($variantsData, 'id');
                $variants = $prodVarHlp->orm()->where_in('id', $variantIds)->find_many_assoc();
                foreach($variantsData as $arr) {
                    $data = [
                        'product_id' => $pId,
                        'variant_sku' => $arr['variant_sku'],
                        'variant_price' => $arr['variant_price'],
                        'variant_qty' => $arr['variant_qty'],
                        'field_values' => json_encode($arr['field_values']),
                        'data_serialized' => json_encode(['variant_file_id' => $arr['variant_file_id']]),
                    ];
                    if (!empty($variants[$arr['id']])) {
                        $variants[$arr['id']]->set($data)->save();
                    } else {
                        $prodVarHlp->create($data)->save();
                    }
                }
            }
//            $model->setData('variants', json_decode($data['variants'], true));
        }
    }
}
