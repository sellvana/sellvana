<?php

/**
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_ShopperFields_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'shopper_fields' => (('Product Shopper Fields')),
        ]);
    }

    public function onProductFormPostAfterValidate($args)
    {
        $model = $args['model'];
        $data = $args['data'];

        if (!empty($data['prod_frontend_data'])) {
            $model->setData('frontend_fields', $this->BUtil->fromJson($data['prod_frontend_data']));
        }
    }

    /**
     * @param Sellvana_Catalog_Model_Product $model
     * @return array
     */
    public function frontendFieldGrid(Sellvana_Catalog_Model_Product $model)
    {
        $data = $model->getData('frontend_fields');
        if (!isset($data)) {
            $data = [];
        }

        // Get allowed locales for render in multi-language module
        $locales = $this->BLocale->parseAllowedLocalesToOptions();
        $config = [
            'config' => [
                'id' => 'frontend-field-grid',
                'caption' => (('Frontend Field Grid')),
                'data_mode' => 'local',
                'data' => $data,
                'columns' => [
                    ['type' => 'row_select', 'width' => 80],
                    ['type' => 'btn_group', 'width' => 80, 'buttons' => [
                        ['name' => 'edit-custom', 'callback' => 'showModalToEditShopperField', 'cssClass' => " btn-xs btn-edit ", "icon" => " icon-dollar ", 'title' => (('Edit Prices'))],
                        ['name' => 'delete'],
                    ]],
                    ['name' => 'id', 'label' => (('ID')), 'width' => 30, 'hidden' => true],
                    ['name' => 'name', 'label' => (('Field Name')), 'multilang' => ['id' => 'name', 'locales' => $locales, 'callback' => 'saveShopperLangField'], 'width' => 200, 'editable' => 'inline',
                        'addable' => true, 'type' => 'input', 'validation' => ['required' => true]],
                    ['name' => 'label', 'label' => (('Field Label')), 'multilang' => ['id' => 'label', 'locales' => $locales, 'callback' => 'saveShopperLangField'], 'editable' => 'inline',
                        'addable' => true, 'type' => 'input' , 'validation' => ['required' => true]],
                    ['name' => 'input_type', 'label' => (('Field Type')), 'width' => 120, 'editable' => 'inline','editor' => 'select',
                        'addable' => true, 'type' => 'input' , 'validation' => ['required' => true], 'default' => 'select',
                        'options' => ['textarea' => (('Text Area')), 'text' => (('Text Line')), 'select' => (('Drop Down')), 'checkbox' => (('Check Box'))], 'select2' => true, 'callback' => 'updateValidation'],
                    ['name' => 'required', 'label' => (('Required')), 'width' => 150, 'editor' => 'select', 'editable' => 'inline', 'type' => 'input', 'addable' => true, 'options' => [1 => (('Yes')), 0 => (('No'))], 'default' => 1, 'select2' => true],
                    ['type' => 'input', 'name' => 'position', 'label' => (('Position')), 'width' => 80, 'editable' => 'inline',
                        'addable' => true, 'validation' => ['number' => true]],
                    ['name' => 'group', 'label' => (('Group Name')), 'width' => 80, 'editable' => 'inline',
                        'addable' => true, 'type' => 'input', 'validation' => []],
                    ['name' => 'qty_min', 'label' => (('Qty Min')), 'width' => 80, 'editable' => 'inline',
                        'addable' => true, 'type' => 'input', 'validation' => []],
                    ['name' => 'qty_max', 'label' => (('Qty Max')), 'width' => 80, 'editable' => 'inline',
                        'addable' => true, 'type' => 'input', 'validation' => []],

            ],
                'filters' => [
                    ['field' => 'name', 'type' => 'text'],
                    ['field' => 'label', 'type' => 'text'],
                    ['field' => 'group', 'type' => 'text'],
                    ['field' => 'input_type', 'type' => 'multiselect'],
                    ['field' => 'required', 'type' => 'multiselect'],
                    ['field' => 'options', 'type' => 'text']
                ],
                'actions' => [
                    //'new' => ['caption' => 'Add Fields'],
                    'add-blank-row' => [
                        'caption'  => (('Add Fields')),
                        'type'     => 'button',
                        'id'       => 'add-blank-row',
                        'class'    => 'btn-primary',
                        'callback' => 'addBlankRows'
                    ],
                    'delete' => ['caption' => (('Remove'))]
                ],
                'callbacks' => [
                    'componentDidMount' => 'fieldsGridRegister'
                ],
                'grid_before_create' => 'frontendFieldGridRegister'
            ]
        ];

        return $config;
    }

    public function frontendOptionsGrid() {
        $config = [
            'config' => [
                'id' => 'options-grid',
                'caption' => (('Options Grid')),
                'data_mode' => 'local',
                'data' => [],
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => (('ID')), 'width' => 30, 'hidden' => true],
                    ['type' => 'input', 'name' => 'label', 'label' => (('Option Name (Bundle Part Name)')), 'width' => 300, 'editable' => 'inline', 'sortable' => false, 'validation' => ['required' => true], 'callback' => 'editShopperOptionLabelCallback', 'cssClass' => 'optionLabelUnique '],
                    ['type' => 'input', 'name' => 'sku', 'label' => (('Sku')), 'width' => 150, 'editable' => 'inline', 'sortable' => false],
                    ['type' => 'input', 'name' => 'position', 'label' => (('Position')), 'width' => 100, 'editable' => 'inline', 'sortable' => false, 'validation' => ['required' => true], 'cssClass' => 'optionPositionUnique ', 'callback' => 'editShopperOptionPositionCallback'],
                    ['type' => 'btn_group', 'buttons' => [
                            ['name' => 'edit-custom', 'callback' => 'editShopperOption', 'cssClass' => " btn-xs btn-edit ", 'textValue' => (('Edit Price')), "icon" => " icon-dollar", 'attrs' => ['data-toggle' => 'tooltip', 'title' => (('Update Prices')), 'data-placement' => 'top']],
                            ['name' => 'delete', 'callback' => 'deleteOptionRowCallback']
                        ]
                    ]
                ],
                'filters' => [
                    '_quick' => ['expr' => 'field_code like ? or id like ', 'args' => ['%?%', '%?%']]
                ],
                'actions' => [
                    //'new' => ['caption' => 'Add Fields'],
                    'add-new-field-option' => [
                        'caption'  => (('Add New Option')),
                        'type'     => 'button',
                        'id'       => 'add-new-field-option',
                        'class'    => 'btn-primary',
                        'callback' => 'insertNewFieldOption'
                    ],
                    'add-prices' => [
                        'caption'  => (('Apply Prices')),
                        'type'     => 'button',
                        'id'       => 'add-prices',
                        'class'    => 'btn-info',
                        'callback' => 'applyPricesForAllFields'
                    ],
                    'delete' => ['caption' => (('Remove'))]
                ],
                'callbacks' => [
                    'componentDidMount' => 'optionsGridRegister'
                ],
                'grid_before_create' => 'optionsGridRegister'
            ]
        ];

        return $config;

    }
}
