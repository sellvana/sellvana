<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogFields_Admin
 *
 * @property Sellvana_CatalogFields_Model_Field $Sellvana_CatalogFields_Model_Field
 * @property Sellvana_CatalogFields_Model_FieldOption $Sellvana_CatalogFields_Model_FieldOption
 * @property Sellvana_CatalogFields_Model_ProductField $Sellvana_CatalogFields_Model_ProductField
 * @property Sellvana_CatalogFields_Model_ProductVarfield $Sellvana_CatalogFields_Model_ProductVarfield
 * @property Sellvana_CatalogFields_Model_ProductVariant $Sellvana_CatalogFields_Model_ProductVariant
 * @property Sellvana_CatalogFields_Model_ProductVariantField $Sellvana_CatalogFields_Model_ProductVariantField
 * @property Sellvana_CatalogFields_Model_ProductVariantImage $Sellvana_CatalogFields_Model_ProductVariantImage
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 */
class Sellvana_CatalogFields_Admin extends BClass
{
/*
    public function productAfterSave($args)
    {
        $p = $args['model'];
        $data = $p->as_array();
        $fields = $this->Sellvana_CatalogFields_Model_Field->fieldsInfo('product', true);
        if (array_intersect($fields, array_keys($data))) {
            $custom = $this->Sellvana_CatalogFields_Model_ProductField->load($p->id, 'product_id');
            if (!$custom) {
                $custom = $this->Sellvana_CatalogFields_Model_ProductField->create();
            }
            $custom->set('product_id', $p->id)->set($data)->save();
        }
        // not deleting to preserve meta info about fields
    }
*/
    public function onProductGridColumns($args)
    {
        /** @var Sellvana_CatalogFields_Model_Field[] $fields */
        $fields = $this->Sellvana_CatalogFields_Model_Field->orm('f')->find_many();
        foreach ($fields as $f) {
            $col = ['label' => $f->field_name, 'index' => 'pcf.' . $f->field_name, 'hidden' => true];
            if ($f->admin_input_type == 'select') {
                $col['options'] = $this->Sellvana_CatalogFields_Model_FieldOption->orm()
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
        #$p = $this->Sellvana_Catalog_Model_Product->load($id);

        if (!$p) {
            return;//$p = $this->Sellvana_Catalog_Model_Product->create();
        }

        $fieldsOptions = [];
        $fields = $this->Sellvana_CatalogFields_Model_ProductField->productFields($p);
        if ($fields) {
            $fieldIds = $this->BUtil->arrayToOptions($fields, 'id');
            $fieldOptionsAll = $this->Sellvana_CatalogFields_Model_FieldOption->orm()->where_in("field_id", $fieldIds)
                ->order_by_asc('field_id')->order_by_asc('label')->find_many();
            foreach ($fieldOptionsAll as $option) {
                $fieldsOptions[$option->get('field_id')][] = $option;
            }
        }
        $view = $this->BLayout->view('catalogfields/products/fields-partial');
        $view->set('model', $p)->set('fields', $fields)->set('fields_options', $fieldsOptions);
    }

    protected function _saveProductCustom($p, $data = [])
    {
        $pc = $this->Sellvana_CatalogFields_Model_ProductField->load($p->id, 'product_id');
        if (!$pc) {
            $pc = $this->Sellvana_CatalogFields_Model_ProductField->create(['product_id' => $p->id()]);
        }
        if (empty($data) || $data === '[]') {
            $data = null;
        }
        $fieldSets = $this->BUtil->fromJson($data);

        if (is_array($fieldSets) && count($fieldSets)) {

            $fieldNames = [];
            foreach ($fieldSets as $fieldSet) {
                if (!empty($fieldSet['fields'])) {
                    foreach ($fieldSet['fields'] as $field) {
                        $fieldNames[] = $field['field_name'];
                    }
                }
            }
            $fields = $this->Sellvana_CatalogFields_Model_Field->orm()->where_in('field_name', $fieldNames)
                ->find_many_assoc('field_name');

            foreach ($fieldSets as $fieldSet) {
                if (!empty($fieldSet['fields'])) {
                    foreach ($fieldSet['fields'] as $field) {

                        if (empty($fields[$field['field_name']])) {
                            continue;
                        }
                        $field['field_code'] = $fields[$field['field_name']]->get('field_code');
                        $field['column_type'] = $fields[$field['field_name']]->get('table_field_type');

                        if (!in_array($field['field_code'], ['id', 'product_id']) && $field['column_type'] !== 'serialized') {
                            $value = $field['value'];
                            if (!empty($field['options'])) {
                                if (!empty($field['options'][$field['value']])) {
                                    $value = $field['options'][$field['value']];
                                } else {
                                    $value = null;
                                }
                            }
                            $pc->set($field['field_code'], $value);
                        }
                    }
                }
            }
        }
        $pc->set('_data_serialized', $data)->save();
    }

    public function onProductFormPostAfterValidate($args)
    {
        /** @var Sellvana_Catalog_Model_Product $model */
        $model = $args['model'];
        $data = $args['data'];

        if (!empty($data['custom_fields'])) {
            // Save custom fields on fcom_product_custom
            $this->_saveProductCustom($model, $data['custom_fields']);
            // $model->setData('custom_fields', $data['custom_fields']);
        }

        if (empty($data['vfields']) && empty($data['variants'])) {
            return;
        }

        // get new variant fields data from form
        $varFieldsData = [];
        if (!empty($data['vfields'])) {
            $varFieldsPost = $this->BUtil->fromJson($data['vfields']);
            foreach ($varFieldsPost as $vf) {
                $varFieldsData[(int)$vf['id']] = $vf;
            }
        }

        $variantsData = [];
        if (!empty($data['variants'])) {
            $variantsData = $this->BUtil->fromJson($data['variants']);
        }
        //$variantsDataIds = $this->BUtil->arrayToOptions($variantsData, 'id');

        #echo "<pre>"; var_dump($data, '<hr>', $varFieldsData, '<hr>', $variantsData); exit;

        $pId = $model->id();

        // retrieve variant fields already associated to product
        $prodVarfieldHlp = $this->Sellvana_CatalogFields_Model_ProductVarfield;
        /** @var Sellvana_CatalogFields_Model_ProductVarfield[] $prodVarfieldModels */
        $prodVarfieldModels = $prodVarfieldHlp->orm()->where('product_id', $pId)->find_many_assoc('field_id');

        $prodVariantHlp = $this->Sellvana_CatalogFields_Model_ProductVariant;

        //delete variants in remove list
        if (!empty($data['variants_remove'])) {
            $variantRemoveIds = explode(',', $data['variants_remove']);
            $prodVariantHlp->delete_many(['product_id' => $pId, 'id' => $variantRemoveIds]);
        }

        // retrieve product variant models
        $prodVariantModels = $prodVariantHlp->orm()->where('product_id', $pId)->find_many_assoc();

        // delete removed fields
        if ($prodVarfieldModels) {
            $fieldIdsToDelete = array_diff(array_keys($prodVarfieldModels), array_keys($varFieldsData));
            if ($fieldIdsToDelete) {
                $prodVarfieldHlp->delete_many(['product_id' => $pId, 'field_id' => $fieldIdsToDelete]);
                foreach ($prodVarfieldModels as $fId => $m) {
                    if (in_array($fId, $fieldIdsToDelete)) {
                        unset($prodVarfieldModels[$fId]);
                    }
                }
            }
        }
        /** @var Sellvana_CatalogFields_Model_Field[] $fieldModels */
        $fieldModels = [];
        $fieldsByCode = [];
        if ($varFieldsData) {
            // retrieve custom fields
            $fieldHlp = $this->Sellvana_CatalogFields_Model_Field;
            $fieldModels = $fieldHlp->orm()->where_in('id', array_keys($varFieldsData))->find_many_assoc();
            foreach ($fieldModels as $m) {
                $fieldsByCode[$m->get('field_code')] = $m->id();
            }

            // update or create product variant fields
            $pos = 0;
            foreach ($varFieldsData as $vfId => $vf) {
                if (empty($prodVarfieldModels[$vfId])) {
                    $prodVarfieldModels[$vfId] = $prodVarfieldHlp->create(['product_id' => $pId, 'field_id' => $vfId]);
                }
                $prodVarfieldModels[$vfId]->set(['field_label' => $vf['name'], 'position' => $pos])->save();
                $pos++;
            }
        }

        if ($variantsData) {
            // retrieve related custom fields options
            $fieldOptionHlp = $this->Sellvana_CatalogFields_Model_FieldOption;
            /** @var Sellvana_CatalogFields_Model_FieldOption[] $fieldOptionsModels */
            $fieldOptionsModels = $fieldOptionHlp->orm()->where_in('field_id', array_keys($varFieldsData))->find_many();
            $fieldOptionLabelsById = [];
            $fieldOptionIdsByLabel = [];
            $fieldOptionIdsByCodeLabel = [];
            foreach ($fieldOptionsModels as $m) {
                $fieldId = $m->get('field_id');
                $fieldCode = $fieldModels[$fieldId]->get('field_code');
                $label = $m->get('label');
                $fieldOptionLabelsById[$fieldId][$m->id()] = $label;
                $fieldOptionIdsByLabel[$fieldId][$label] = $m->id();
                $fieldOptionIdsByCodeLabel[$fieldCode][$label] = $m->id();
            }

            // retrieve related product variants field values and associate with variants
            $prodVariantFieldHlp = $this->Sellvana_CatalogFields_Model_ProductVariantField;
            /** @var Sellvana_CatalogFields_Model_ProductVariantField[] $prodVariantFieldModels */
            $prodVariantFieldModels = $prodVariantFieldHlp->orm()->where('product_id', $pId)->find_many_assoc('id');
            $prodVariantFieldsArr = [];
            foreach ($prodVariantFieldModels as $m) {
                $f = $fieldModels[$m->get('field_id')];
                $v = $fieldOptionLabelsById[$m->get('field_id')][$m->get('option_id')];
                // TODO: implement locates for field option labels
                $prodVariantFieldsArr[$m->get('variant_id')][$f->get('field_code')] = ['label' => $v, 'id' => $m->id()];
            }
#echo "<pre>"; var_dump($prodVariantFieldsArr); echo "</pre>";
            // match variants from form data to already existing variants by key fields values
            $matchedVariants = [];
            $variantInventorySkus = [];
#echo "<pre>"; var_dump($variantsData); exit;
            foreach ($variantsData as $i => &$vd) {
                if ($vd['inventory_sku'] !== '') {
                    $variantInventorySkus[$i] = $vd['inventory_sku'];
                }
                foreach ($prodVariantModels as $vId => $vm) {
                    if (empty($prodVariantFieldsArr[$vId])) {
                        continue;
                    }
                    $match = true;
                    foreach ($vd['field_values'] as $f => $fv) {
                        if (empty($prodVariantFieldsArr[$vId][$f]['label'])
                            || $prodVariantFieldsArr[$vId][$f]['label'] !== $fv
                        ) {
                            $match = false;
                            break;
                        }
                    }
                    if ($match) {
                        $matchedVariants[$i] = $vId;
                        break;
                    }
                }
            }
            unset($vd);

            // delete unmatched variant models
            $where = ['product_id' => $pId];
            if (!empty($matchedVariants)) {
                $where['NOT'] = ['id' => $matchedVariants];
            }
            $prodVariantHlp->delete_many($where);

            $invHlp = $this->Sellvana_Catalog_Model_InventorySku;
            if ($variantInventorySkus) {
                $invModels = $invHlp->orm()->where_in('inventory_sku', $variantInventorySkus)
                    ->find_many_assoc('inventory_sku');
            } else {
                $invModels = [];
            }

            // update matched variant models and create new variants
            foreach ($variantsData as $i => $vd) {
                if (!empty($matchedVariants[$i])) {
                    $m = $prodVariantModels[$matchedVariants[$i]];
                } else {
                    $m = $prodVariantHlp->create(['product_id' => $pId]);
                }
                if (!empty($vd['inventory_sku']) && $vd['variant_qty'] !== '') {
                    if (empty($invModels[$vd['inventory_sku']])) {
                        $invModels[$vd['inventory_sku']] = $invHlp->create([
                            'inventory_sku' => $vd['inventory_sku'],
                        ])->save();
                    }
                    $invModels[$vd['inventory_sku']]->set([
                        'qty_in_stock' => $vd['variant_qty'],
                    ])->save();
                }
                $m->set([
                    'field_values'  => $this->BUtil->toJson($vd['field_values']),
                    'product_sku'   => $vd['product_sku']   !== '' ? $vd['product_sku']   : null,
                    'inventory_sku' => $vd['inventory_sku'] !== '' ? $vd['inventory_sku'] : null,
                    'variant_price' => $vd['variant_price'] !== '' ? $vd['variant_price'] : null,
                    'manage_inventory' => $vd['variant_qty'] !== '' ? 1 : 0,
                ])->save();
                if (empty($matchedVariants[$i])) {

                    foreach ($vd['field_values'] as $f => $fv) {
                        if (empty($fieldOptionIdsByCodeLabel[$f][$fv])) { // new field option value
                            $fieldId = $fieldsByCode[$f];
                            $newOption = $fieldOptionHlp->create(['field_id' => $fieldId, 'label' => $fv])->save();
                            $newOptionId = $newOption->id();
                            $fieldOptionsModels[$newOptionId] = $newOption;
                            $fieldOptionLabelsById[$fieldId][$newOptionId] = $fv;
                            $fieldOptionIdsByLabel[$fieldId][$fv] = $newOptionId;
                            $fieldOptionIdsByCodeLabel[$f][$fv] = $newOptionId;
                        }
                    }

                    $prodVariantModels[$m->id()] = $m;
                    $matchedVariants[$i] = $m->id();
                    foreach ($vd['field_values'] as $f => $fv) {
                        $fId = $fieldsByCode[$f];
                        $prodVariantFieldHlp->create([
                            'product_id'  => $pId,
                            'variant_id'  => $m->id(),
                            'field_id'    => $fId,
                            'varfield_id' => $prodVarfieldModels[$fId]->id(),
                            'option_id'   => $fieldOptionIdsByLabel[$fId][$fv],
                        ])->save();
                    }
                } else {
                    foreach ($vd['field_values'] as $f => $fv) {
                        $prodVariantField = $prodVariantFieldModels[$prodVariantFieldsArr[$vId][$f]['id']];
                        $fId = $fieldsByCode[$f];
                        $prodVariantField->set([
                            'option_id' => $fieldOptionIdsByLabel[$fId][$fv],
                        ])->save();
                    }
                }
            }

            // retrieve related product variants images
            $prodVariantImageHlp = $this->Sellvana_CatalogFields_Model_ProductVariantImage;
            $prodVariantImageModels = $prodVariantImageHlp->orm()->where('product_id', $pId)->find_many();
            $prodVariantImages = [];
            foreach ($prodVariantImageModels as $m) {
                $prodVariantImages[$m->get('variant_id')][$m->get('file_id')] = $m;
            }

            // update and create variant images
            $fileIdsToDelete = [];
            foreach ($variantsData as $i => $vd) {
                $dataFileIds = !empty($vd['variant_file_id']) ? explode(',', $vd['variant_file_id']) : [];
                $vId = $matchedVariants[$i];
                if (!empty($prodVariantImages[$vId])) {
                    foreach ($prodVariantImages[$vId] as $fileId => $m) {
                        if (!in_array($fileId, $dataFileIds)) {
                            $fileIdsToDelete[] = $m->id();
                        }
                    }
                }
                foreach ($dataFileIds as $pos => $fileId) {
                    if (!empty($prodVariantImages[$vId][$fileId])) {
                        $prodVariantImages[$vId][$fileId]->set('position', $pos)->save();
                    } else {
                        $prodVariantImageHlp->create([
                            'product_id' => $pId,
                            'variant_id' => $vId,
                            'file_id'    => $fileId,
                            'position'   => $pos,
                        ])->save();
                    }
                }
            }
            // delete unused variant images
            if ($fileIdsToDelete) {
                $prodVariantImageHlp->delete_many(['product_id' => $pId, 'id' => $fileIdsToDelete]);
            }
        }
    }
}
