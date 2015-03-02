<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_ShopperFields_Admin extends BClass
{
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
                    ['name' => 'name', 'label' => 'Field Name', 'width' => 200, 'editable' => 'inline',
                        'addable' => true, 'type' => 'input' , 'validation' => ['required' => true]],
                    ['name' => 'label', 'label' => 'Field Label', 'width' => 200, 'editable' => 'inline',
                        'addable' => true, 'type' => 'input' , 'validation' => ['required' => true]],
                    ['name' => 'input_type', 'label' => 'Field Type', 'width' => 200, 'editable' => 'inline','editor' => 'select',
                        'addable' => true, 'type' => 'input' , 'validation' => ['required' => true], 'default' => 'select',
                        'options' => ['textarea' => 'Text Area', 'text' => 'Text Line', 'select' => 'Drop Down', 'checkbox' => 'Check Box'],
                    ],
                    ['name' => 'required', 'label' => 'Required', 'width' => 150, 'editor' => 'select',
                        'editable' => 'inline', 'type' => 'input', 'addable' => true, 'options' => [1 => 'Yes', 0 => 'No'], 'default' => 1],
                    ['type' => 'input', 'name' => 'options', 'label' => 'Options', 'width' => 200, 'editable' => 'inline',
                        'addable' => true],
                    ['type' => 'input', 'name' => 'position', 'label' => 'Position', 'width' => 200, 'editable' => 'inline',
                        'addable' => true, 'validation' => ['number' => true]],
                    ['type' => 'btn_group', 'buttons' => [['name' => 'delete']]]
                ],
                'actions' => [
                    'new' => ['caption' => 'Add Fields'],
                    'delete' => ['caption' => 'Remove']
                ],
                'grid_before_create' => 'frontendFieldGridRegister'
            ]
        ];

        return $config;
    }
}