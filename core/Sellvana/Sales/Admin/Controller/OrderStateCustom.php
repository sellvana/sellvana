<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Admin_Controller_OrderStateCustom
 *
 * @property Sellvana_Sales_Model_StateCustom $Sellvana_Sales_Model_StateCustom
 */

class Sellvana_Sales_Admin_Controller_OrderStateCustom extends FCom_Admin_Controller_Abstract
{

    protected $_permission = 'sales/order_custom_state';

    public function action_index()
    {
        $this->layout('/orderstatecustom');
    }

    public function gridConfig()
    {
        $orm = $this->Sellvana_Sales_Model_StateCustom->orm('oscs')->select('oscs.*');

        $config = [
            'config' => [
                'id'     => 'state-custom',
                'caption' => 'State Custom',
                'data_url' => $this->BApp->href('/orderstatecustom/grid_data'),
                'edit_url' => $this->BApp->href('/orderstatecustom/grid_data'),
                'orm' => $orm,
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'index' => 'oscs.id', 'label' => 'ID', 'width' => 40],
                    ['name' => 'entity_type', 'index' => 'sc.entity_type', 'label' => 'Entity Type','width' => 85, 'addable'=>true,'editable' => true, 'editor' => 'select', 'options' => $this->Sellvana_Sales_Model_StateCustom->fieldOptions('entity_type'), 'validation' => ['required' => true]],
                    ['name' => 'state_code', 'index' => 'oscs.state_code', 'label' => 'Code', 'width' =>  150, 'addable'=>true, 'editable' => true, 'validation' => ['required' => true, 'unique' => $this->BApp->href('orderstatecustom/unique')]],
                    ['name' => 'state_label', 'index' => 'oscs.state_label', 'label' => 'Label' ,'width' => 150, 'addable'=>true, 'editable' => true, 'validation' => ['required' => true, 'unique' => $this->BApp->href('orderstatecustom/unique')]],
                    ['type' => 'btn_group', 'buttons' => [
                            ['name' => 'edit'],
                            ['name' => 'delete']
                        ]
                    ]
                ],
                'actions' => [
                    'edit' => true,
                    'delete' => true
                ],
                'filters' => [
                    ['field' => 'entity_type', 'type' => 'text'],
                    ['field' => 'state_code', 'type' => 'text'],
                    ['field' => 'state_label', 'type' => 'text'],
                ],
                'grid_before_create' => 'orderCustomStateGridRegister'
            ]
        ];

        return $config;
    }

    public function action_order_state_custom()
    {
        $this->layout('/orderstatecustom');
    }

    /**
     * get data
     */
    public function action_grid_data()
    {
        $view = $this->view('core/backbonegrid');
        $view->set('grid', $this->gridConfig());
        $data = $view->generateOutputData();
        $this->BResponse->json([
            ['c' => $data['state']['c']],
            $this->BDb->many_as_array($data['rows']),
        ]);
    }

    /**
     * process POST submitted data
     */

    public function action_grid_data__POST()
    {
        $this->_processGridDataPost('Sellvana_Sales_Model_StateCustom');
    }

    /**
     * ajax check code is unique
     */
    public function action_unique__POST()
    {
        try {
            $post = $this->BRequest->post();
            $data = each($post);
            if (!isset($data['key']) || !isset($data['value'])) {
                throw new BException('Invalid post data');
            }
            $key = $this->BDb->sanitizeFieldName($data['key']);
            $value = $data['value'];

            $exists = $this->Sellvana_Sales_Model_StateCustom->load($value, $key);
            $result = ['unique' => !$exists, 'id' => !$exists ? -1 : $exists->id()];
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }
}
