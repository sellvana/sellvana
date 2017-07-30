<?php

/**
 * Class Sellvana_Sales_Admin_Controller_OrderStateCustom
 *
 * @property Sellvana_Sales_Model_StateCustom $Sellvana_Sales_Model_StateCustom
 */

class Sellvana_Sales_Admin_Controller_OrderStateCustom extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'sales/order_custom_state';

    protected $_navPath = 'sales/orderstatecustom';

    protected $_gridHref = 'orderstatecustom';
    protected $_modelClass = 'Sellvana_Sales_Model_StateCustom';
    protected $_gridTitle = (('Order Custom States'));
    protected $_gridLayoutName = '/orderstatecustom';
    protected $_recordName = (('Custom State'));
    protected $_mainTableAlias = 'oscs';
    protected $_formViewPrefix = 'order/customstates-form/';

    public function gridConfig()
    {
        $entityTypes = $this->Sellvana_Sales_Model_StateCustom->fieldOptions('entity_type');

        $config = parent::gridConfig();
        $config['id'] = __CLASS__;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete'],
            ]],
            ['name' => 'id', 'index' => 'oscs.id', 'label' => (('ID')), 'width' => 40],
            ['name' => 'entity_type', 'index' => 'sc.entity_type', 'label' => (('Entity Type')),'width' => 85,
                'addable'=>true,'editable' => true, 'editor' => 'select', 'options' => $entityTypes,
                'validation' => ['required' => true]],
            ['name' => 'state_code', 'index' => 'oscs.state_code', 'label' => (('Code')), 'width' =>  150, 'addable'=>true,
                'editable' => true, 'validation' => ['required' => true, 'unique' => $this->BApp->href('orderstatecustom/unique')]],
            ['name' => 'state_label', 'index' => 'oscs.state_label', 'label' => (('Label')) ,'width' => 150, 'addable'=>true,
                'editable' => true, 'validation' => ['required' => true, 'unique' => $this->BApp->href('orderstatecustom/unique')]],
        ];

        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'entity_type', 'type' => 'multiselect'],
            ['field' => 'state_code', 'type' => 'text'],
            ['field' => 'state_label', 'type' => 'text'],
        ];

        $config['grid_before_create'] = 'orderCustomStateGridRegister';

        return $config;
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
