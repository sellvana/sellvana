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
 * @property Sellvana_CatalogFields_Model_ProductFieldData    $Sellvana_CatalogFields_Model_ProductFieldData
 * @property Sellvana_CatalogFields_Main $Sellvana_CatalogFields_Main
 * @property Sellvana_MultiSite_Admin $Sellvana_MultiSite_Admin
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
            $fieldIds = $this->BUtil->arrayToOptions($fields, '.id');
            $fieldOptionsAll = $this->Sellvana_CatalogFields_Model_FieldOption->orm()->where_in("field_id", $fieldIds)
                ->order_by_asc('field_id')->order_by_asc('label')->find_many();
            foreach ($fieldOptionsAll as $option) {
                $fieldsOptions[$option->get('field_id')][] = $option;
            }
        }
        $view = $this->BLayout->view('catalogfields/products/fields-partial');
        $view->set('model', $p)->set('fields', $fields)->set('fields_options', $fieldsOptions);
    }

    public function onProductFormPostAfterValidate($args)
    {
        $this->Sellvana_CatalogFields_Main->disable();

        /** @var Sellvana_Catalog_Model_Product $model */
        $model = $args['model'];
        $data = &$args['data'];

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
                    $tmpId  = $vd['id'];
                    $m      = $prodVariantHlp->create(['product_id' => $pId]);
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

                if ($m->isNewRecord() && !empty($data['variantPrice'])) {
                    $this->_setVariantId($m, $tmpId, $data['variantPrice']);
                }

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

    /**
     * Update variant_id for each price of each new variant after saved ( Only has prices data )
     * @param [object] $variantModel
     * @param [string] $tmpId
     * @param [array] &$variantsPrices
     */
    protected function _setVariantId($variantModel, $tmpId, &$variantsPrices) {
        if (!empty($variantsPrices['prices'])) {
            foreach ($variantsPrices['prices'] as $vId => $data) {
                parse_str($data, $prices);
                foreach ($prices['variantPrice'] as $id => $price) {
                    if ($price['variant_id'] == $tmpId) {
                        $prices['variantPrice'][$id]['variant_id'] = $variantModel->id();
                    }
                }
                $variantsPrices['prices'][$vId] = http_build_query($prices);
            }
        }
    }

    public function onProductFormPostBefore($args)
    {
        /** @var Sellvana_Catalog_Model_Product $product */
        $customFieldsData = $this->BUtil->fromJson($this->BRequest->post('custom_fields'));
        $product = &$args['model'];
        if (!$customFieldsData || !$product->id()) {
            return;
        }

        $productFields = $this->Sellvana_CatalogFields_Model_ProductFieldData->orm('pf')
            ->where_equal('product_id', $product->id())
            ->group_by('field_id')
            ->find_many_assoc('field_id', 'field_id');

        foreach ($customFieldsData as $fieldSetData) {
            foreach ($fieldSetData['fields'] as $fieldData) {
                if (in_array($fieldData['id'], $productFields)) {
                    unset($productFields[$fieldData['id']]);
                }
            }
        }
        $product->set('_custom_fields_remove', $productFields);

        if (!empty($customFieldsData)) {
            // Save custom fields on fcom_product_custom
            $product->set('custom_fields', $customFieldsData);
            //$this->_processProductCustom($model, $this->BUtil->fromJson($data['custom_fields']));
            // $model->setData('custom_fields', $data['custom_fields']);
        }
    }
}
