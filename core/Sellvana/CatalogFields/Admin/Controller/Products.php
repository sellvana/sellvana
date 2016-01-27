<?php

/**
 * Class Sellvana_CatalogFields_Admin_Controller_Products
 *
 * @property Sellvana_CatalogFields_Model_ProductFieldData $Sellvana_CatalogFields_Model_ProductFieldData
 * @property Sellvana_CatalogFields_Model_ProductVariant $Sellvana_CatalogFields_Model_ProductVariant
 * @property Sellvana_CatalogFields_Model_FieldOption $Sellvana_CatalogFields_Model_FieldOption
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CatalogFields_Model_Set $Sellvana_CatalogFields_Model_Set
 * @property Sellvana_CatalogFields_Model_SetField $Sellvana_CatalogFields_Model_SetField
 * @property Sellvana_CatalogFields_Model_Field $Sellvana_CatalogFields_Model_Field
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 * @property FCom_Core_Main $FCom_Core_Main
 * @property Sellvana_CatalogFields_Model_ProductVarfield $Sellvana_CatalogFields_Model_ProductVarfield
 * @property Sellvana_CatalogFields_Model_ProductVariantImage $Sellvana_CatalogFields_Model_ProductVariantImage
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_CatalogFields_Model_ProductField $Sellvana_CatalogFields_Model_ProductField
 */
class Sellvana_CatalogFields_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    /**
     * @return array
     */
    public function fieldsetsGridConfig()
    {
        $config = [
            'grid' => [
                'id'      => 'product_fieldsets',
                'caption' => 'Field Sets',
                'url' => $this->BApp->href('catalogfields/fieldsets/grid_data'),
                'orm' => 'Sellvana_CatalogFields_Model_SetField',
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

    /**
     * @param Sellvana_Catalog_Model_Product $model
     * @return array
     */
    public function variantFieldGridConfig($model)
    {
        //$data = $model->getData('variants_fields');
        $varFields = $this->Sellvana_CatalogFields_Model_ProductVarfield->orm('vf')
            ->join('Sellvana_CatalogFields_Model_Field', ['f.id', '=', 'vf.field_id'], 'f')
            ->select(['varfield_id' => 'vf.id', 'vf.field_id', 'varfield_label' => 'vf.field_label', 'vf.position'])
            ->select(['f.field_code', 'f.field_name'])
            ->where('product_id', $model->id)
            ->order_by_asc('vf.position')
            ->find_many_assoc('field_id');
        if ($varFields) {
            $varFieldsOptions = $this->Sellvana_CatalogFields_Model_FieldOption->orm()
                ->where_in('field_id', array_keys($varFields))
                ->find_many_assoc();
            $options = [];
            foreach ($varFieldsOptions as $vfo) {
                /** @var Sellvana_CatalogFields_Model_FieldOption $vfo */
                $options[$vfo->get('field_id')][$vfo->id()] = $vfo->get('label');
            }
        }

        $data = [];
        foreach ($varFields as $vf) {
            /** @var Sellvana_CatalogFields_Model_ProductVarfield $vf */
            $fId = $vf->get('field_id');
            $data[] = [
                'id'          => $fId,
                'varfield_id' => $vf->get('varfield_id'),
                'field_code'  => $vf->get('field_code'),
                'name'        => $vf->get('field_name'),
                'options'     => !empty($options[$fId]) ? $options[$fId] : [],
            ];
        }
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
                'grid_before_create' => 'variantFieldGriddleRegister',
                'callbacks' => [
                    'componentDidMount' => 'variantFieldGriddleRegister'
                ]
            ]
        ];

        return $config;
    }

    /**
     * @param Sellvana_Catalog_Model_Product $product
     * @return array
     */
    public function variantGridConfig($product)
    {
        $thumbUrl = $this->FCom_Core_Main->resizeUrl($this->BConfig->get('web/media_dir') . '/product/images', ['s' => 30]);
        $columns = [
            ['type' => 'row_select'],
            [
                'type' => 'btn_group',  
                'buttons' => [
                    ['name' => 'delete'], 
                    ['name' => 'edit-custom', 'callback' => 'showModalToEditVariantPrice', 'cssClass' => " btn-xs btn-edit ", "icon" => " icon-pencil "]
                ]
            ],
            ['name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true, 'position' => 1]
        ];

        //$vFields = $model->getData('variants_fields');

        $varFields = $this->Sellvana_CatalogFields_Model_ProductVarfield->orm('vf')
            ->join('Sellvana_CatalogFields_Model_Field', ['f.id', '=', 'vf.field_id'], 'f')
            ->select(['varfield_id' => 'vf.id', 'vf.field_id', 'varfield_label' => 'vf.field_label', 'vf.position'])
            ->select(['f.field_code', 'f.field_name'])
            ->where('product_id', $product->id())
            ->order_by_asc('vf.position')
            ->find_many_assoc('field_id');

        if ($varFields) {
            $varFieldsOptions = $this->Sellvana_CatalogFields_Model_FieldOption->orm()
                ->where_in('field_id', array_keys($varFields))
                ->find_many_assoc();
            $options = [];
            foreach ($varFieldsOptions as $vfo) {
                $options[$vfo->get('field_id')][$vfo->get('label')] = $vfo->get('label');
            }
        }

        if ($varFields) {
            $pos = 2;
            foreach ($varFields as $fId => $vf) {
                $f = [];
                $f['options'] = !empty($options[$fId]) ? $options[$fId] : [];
                $f['label'] = $vf->get('field_name');
                $f['name'] = $vf->get('field_code');
                $f['field_id'] = $fId;
                $f['addable'] = true;
                $f['multirow_edit'] = true;
                $f['width'] = 200;
                $f['position'] = $pos++;
                $f['validation'] = ['required' => true];
                $f['display'] = 'eval';
                $f['print'] = '"<p style=\"overflow:hidden\"><input type=\"hidden\" name=\''. $vf->get('field_code').'\' class=\"select-value-field required\" style=\"width: 170px\" /></p>"';
                $f['default'] = '';
                $columns[] = $f;
            }
#var_dump($columns); exit;
        }
        $image = $this->variantImageGrid($product);
        $columns[] = ['type' => 'input', 'name' => 'product_sku', 'label' => 'Variant SKU', 'width' => 150, 'editable' => 'inline', 'addable' => true, 'default' => ''];
        $columns[] = ['type' => 'input', 'name' => 'inventory_sku', 'label' => 'Inventory SKU', 'width' => 150, 'editable' => 'inline', 'addable' => true, 'default' => ''];
        $columns[] = ['type' => 'input', 'name' => 'variant_price', 'label' => 'PRICE', 'width' => 150, 'editable' => 'inline', 'addable' => true, 'validation' => ['number' => true], 'default' => '', 'attrs' => ['readOnly' => 'readOnly']];
        $columns[] = ['type' => 'input', 'name' => 'variant_qty', 'label' => 'QTY', 'width' => 150, 'editable' => 'inline', 'addable' => true, 'validation' => ['number' => true], 'default' => ''];
        $columns[] = ['name' => 'image', 'label' => 'IMAGES', 'width' => 250, 'display' => 'eval',
            'addable' => true, 'sortable' => false, 'print' => '"<input type=\"hidden\" class=\"store-variant-image-id\" value=\'"+ rc.row["variant_file_id"] +"\'/><ol class=\"dd-list columns dd-list-axis-x hide list-variant-image\"></ol><select class=\"form-control variant-image\"><option value></option></select>"' ];
        $columns[] = ['name' => 'variant_file_id',  'hidden' => true];
        $columns[] = ['name' => 'list_image',  'hidden' => true, 'default' => $image];
        $columns[] = ['name' => 'field_values',  'hidden' => true, 'default' => ''];
        $columns[] = ['name' => 'thumb_url',  'hidden' => true, 'default' => $thumbUrl];

        $data = [];

        /** @var Sellvana_CatalogFields_Model_ProductVariant[] $variants */
        $variants = $this->Sellvana_CatalogFields_Model_ProductVariant->orm()->where('product_id', $product->id())->find_many();
        $images = $this->Sellvana_CatalogFields_Model_ProductVariantImage->orm()->where('product_id', $product->id())->find_many();
        $invSkus = [];
        if ($variants !== null) {
            foreach ($variants as $v) {
                $fileIds = [];
                foreach ($images as $img) {
                    if ($img->get('variant_id') == $v->id()) {
                        $fileIds[] = $img->get('file_id');
                    }
                }
                $vField = [];
                $vField['field_values'] = $this->BUtil->fromJson($v->field_values);
                $vField['product_sku'] = $v->product_sku;
                $vField['inventory_sku'] = $v->inventory_sku;
                $vField['variant_qty'] = $v->variant_qty;
                $vField['variant_price'] = $v->getCatalogPrice($product);
                $vField['variant_file_id'] = join(',', $fileIds);
                $vField['id'] = $v->id();
                $data[] = $vField;
                if ($v->inventory_sku) {
                    $invSkus[] = $vField['inventory_sku'];
                }
            }
        }
        if ($invSkus) {
            $skus = $this->Sellvana_Catalog_Model_InventorySku->orm()
                ->where_in('inventory_sku', $invSkus)->find_many_assoc('inventory_sku');
            foreach ($data as $i => $v) {
                if (!empty($skus[$v['inventory_sku']])) {
                    $data[$i]['variant_qty'] = $skus[$v['inventory_sku']]->qty_in_stock;
                }
            }
        }

        // Get prices for each variant
        if (!empty($data)) {
            $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
            foreach ($data as $key => $variant) {
                $data[$key]['prices'] = $priceHlp->getProductPrices($product, $variant['id']);
            }
        }

        $config = [
            'config' => [
                'id' => 'variant-grid-' . $product->id(),
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
                'grid_before_create' => 'variantGriddleRegister',
                'callbacks' => [
                    'componentDidMount' => 'variantGriddleRegister',
                    'componentDidUpdate' => 'variantGriddleRegister'
                ]
            ]
        ];

        return $config;

    }

    /**
     * @param Sellvana_Catalog_Model_Product $model
     * @return array
     */
    public function variantImageGrid($model)
    {
        $data = $this->BDb->many_as_array($model->mediaORM(Sellvana_Catalog_Model_ProductMedia::MEDIA_TYPE_IMG)
            ->left_outer_join('Sellvana_Catalog_Model_ProductMedia', ['pa.file_id', '=', 'pm.file_id'], 'pm')
            ->select(['pa.id', 'pa.position',  'a.file_name'])
            ->select('a.id', 'file_id')
            ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder')
            ->group_by('pa.id')
            ->find_many());
        return $data;
    }

    public function action_field_remove()
    {
        $id = $this->BRequest->param('id', true);
        $p = $this->Sellvana_Catalog_Model_Product->load($id);
        if (!$p) {
            return;
        }
        $hide_field = $this->BRequest->param('hide_field', true);
        if (!$hide_field) {
            return;
        }
        $this->Sellvana_CatalogFields_Model_ProductField->removeField($p, $hide_field);
        $this->BResponse->json('');
    }

    public function action_fields_partial()
    {
        $id = $this->BRequest->param('id', true);
        $p = $this->Sellvana_Catalog_Model_Product->load($id);
        if (!$p) {
            $p = $this->Sellvana_Catalog_Model_Product->create();
        }

        $fields_options = [];
        $fields = $this->Sellvana_CatalogFields_Model_ProductField->productFields($p, $this->BRequest->request());
        foreach ($fields as $field) {
            $fields_options[$field->id()] = $this->Sellvana_CatalogFields_Model_FieldOption->orm()
                ->where("field_id", $field->id())->find_many();
        }

        $view = $this->view('catalogfields/products/fields-partial');
        $view->set('model', $p)->set('fields', $fields)->set('fields_options', $fields_options);
        $this->BLayout->setRootView('catalogfields/products/fields-partial');
        $this->BResponse->render();
    }

    public function getInitialData($model)
    {
        $pId = $model->id();
        $data = $this->Sellvana_CatalogFields_Model_ProductFieldData->getProductFieldSetData([$pId]);
        return !empty($data[$pId]) ? $data[$pId] : [];
    }

    public function fieldsetAry()
    {
        $sets = $this->BDb->many_as_array($this->Sellvana_CatalogFields_Model_Set->orm('s')->select('s.*')->find_many());

        return json_encode($sets);
    }

    public function fieldAry()
    {
        $fields = $this->BDb->many_as_array($this->Sellvana_CatalogFields_Model_SetField->orm('s')->select('s.*')->find_many());

        return json_encode($fields);
    }

    public function action_get_fieldset()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        $set = $this->Sellvana_CatalogFields_Model_Set->load($id);
        $fields = $this->BDb->many_as_array($this->Sellvana_CatalogFields_Model_SetField->orm('sf')
            ->join('Sellvana_CatalogFields_Model_Field', ['f.id', '=', 'sf.field_id'], 'f')
            ->select(['f.id', 'f.field_code', 'f.field_name', 'f.admin_input_type', 'f.required'])
            ->where('sf.set_id', $id)->find_many()
        );
        foreach ($fields as &$field) {
            if ($field['admin_input_type'] === 'select' ||  $field['admin_input_type'] === 'multiselect') {
                $field['options'] = $this->Sellvana_CatalogFields_Model_FieldOption->getFieldOptions($field['id'], false, 'label');
            }
        }

        $this->BResponse->json(['id' => $set->id(), 'set_name' => $set->set_name, 'fields' => ($fields)]);
    }

    public function action_prices() {
        $r = $this->BRequest;
        $variantId = $r->get('variant_id');
        $p = $this->Sellvana_Catalog_Model_Product->load($r->get('id'));
        $prices = $this->Sellvana_Catalog_Model_ProductPrice->getProductPrices($p, $variantId);
        $this->BResponse->json($prices);
    }

    public function action_get_field()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        $field = $this->Sellvana_CatalogFields_Model_Field->load($id);
        $options = $this->Sellvana_CatalogFields_Model_FieldOption->getFieldOptions($field->id(), false, 'label');
        $this->BResponse->json(['id' => $field->id(), 'field_code' => $field->field_code,
            'field_name' => $field->field_name, 'admin_input_type' => $field->admin_input_type,
            'multilanguage' => $field->multilanguage, 'options' => $options, 'required' => $field->required]);
    }

    public function action_save__POST()
    {
        try {
            $data = $this->BRequest->post();
            $prodId = $data['id'];
            $json = $data['json'];
            $hlp = $this->Sellvana_CatalogFields_Model_ProductField;
            $res = $hlp->load($prodId, 'product_id');
            if (!$res) {
                $hlp->create(['product_id' => $prodId, '_data_serialized' => $json])->save();
                $status = 'Successfully saved.';
            } else {
                $res->set('_data_serialized', $json)->save();
                $status = 'Successfully updated.';
            }
        } catch (Exception $e) {
            $status = false;
        }
        $this->BResponse->json(['status' => $status]);
    }

    public function action_get_fields__POST()
    {
        try {
            $res = [];
            $data = $this->BRequest->post();
            $ids = explode(',', $data['ids']);
            $optionsHlp = $this->Sellvana_CatalogFields_Model_FieldOption;
            $fields = $this->Sellvana_CatalogFields_Model_Field->orm()->where('id', $ids)->find_many_assoc();
            foreach ($fields as $id => $field) {
                $res[] = [
                    'id' => $id,
                    'name' => $field->field_name,
                    'label' => $field->frontend_label,
                    'input_type' => $field->admin_input_type,
                    'options' => join(',', array_keys($optionsHlp->getFieldOptions($id))),
                    'required' => $field->required,
                    'field_code' => $field->field_code,
                    'position' => ''
                ];
            }
        } catch (Exception $e) {
            $res = ['error' => $e->getMessage()];
        }

        $this->BResponse->json($res);
    }

    public function getFieldTypes()
    {
        $f = $this->Sellvana_CatalogFields_Model_Field;
        return $f->fieldOptions('table_field_type');
    }

    public function getAdminInputTypes()
    {
        $f = $this->Sellvana_CatalogFields_Model_Field;
        return $f->fieldOptions('admin_input_type');
    }
}
