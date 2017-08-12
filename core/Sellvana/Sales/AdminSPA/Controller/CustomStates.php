<?php

class Sellvana_Sales_AdminSPA_Controller_CustomStates extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    static protected $_origClass = __CLASS__;

    static protected $_modelClass = 'Sellvana_Sales_Model_StateCustom';
    static protected $_modelName = 'state_custom';
    static protected $_recordName = (('Custom State'));

    public function getGridConfig()
    {
        $entityTypes = $this->Sellvana_Sales_Model_StateCustom->fieldOptions('entity_type');

        return [
            static::ID => 'custom_states',
            static::DATA_URL => 'custom_states/grid_data',
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT],
                [static::NAME => 'id', static::LABEL => (('ID'))],
                [static::NAME => 'entity_type', static::LABEL => (('Entity Type')), static::OPTIONS => $entityTypes],
                [static::NAME => 'state_code', static::LABEL => (('Code')),
                    static::DATACELL_TEMPLATE => '<td><a :href="\'#/sales/custom-states/form?id=\'+row.id">{{row.state_code}}</a></td>'],
                [static::NAME => 'state_label', static::LABEL => (('Label')),
                    static::DATACELL_TEMPLATE => '<td><a :href="\'#/sales/custom-states/form?id=\'+row.id">{{row.state_label}}</a></td>'],
            ],
            static::FILTERS => true,
            static::EXPORT => true,
            static::PAGER => true,
            static::BULK_ACTIONS => [
                [static::NAME => 'delete', static::LABEL => (('Delete'))],
            ],
            static::PAGE_ACTIONS => [
                [static::NAME => 'new', static::LABEL => (('Add New Custom State')), static::BUTTON_CLASS => 'button1',
                    static::LINK => '/sales/custom-states/form', static::GROUP => 'new'],
            ]
        ];
    }

    public function getFormData()
    {
        $stateId = $this->BRequest->get('id');
        $bool = [0 => (('no')), 1 => (('Yes'))];

        $entityTypes = $this->Sellvana_Sales_Model_StateCustom->fieldOptions('entity_type');

        if ($stateId) {
            $customState = $this->Sellvana_Sales_Model_StateCustom->load($stateId);
            if (!$customState) {
                throw new BException('User not found');
            }
        } else {
            $customState = $this->Sellvana_Sales_Model_StateCustom->create();
        }

        $result = [];
        $result[static::FORM]['state_custom'] = $customState->as_array();
        $result[static::FORM][static::CONFIG][static::TITLE] = $stateId ? $customState->get('state_label') : (('New Custom State'));
        $result[static::FORM][static::CONFIG][static::TABS] = '/sales/custom-states/form';
        $result[static::FORM][static::CONFIG][static::FIELDS] = [
            static::DEFAULT_FIELD => [static::MODEL => 'state_custom', static::TAB => 'main'],
            [static::NAME => 'entity_type', static::LABEL => (('Entity Type')), static::OPTIONS => $entityTypes],
            [static::NAME => 'state_code', static::LABEL => (('Code'))],
            [static::NAME => 'state_label', static::LABEL => (('Label'))],
        ];

        $result[static::FORM][static::CONFIG][static::PAGE_ACTIONS] = $this->getDefaultFormPageActions();

        return $result;
    }
}