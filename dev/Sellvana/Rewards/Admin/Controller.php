<?php

/**
 * Created by pp
 *
 * @property Sellvana_Rewards_Model_Rule $Sellvana_Rewards_Model_Rule
 * @project sellvana_core
 */
class Sellvana_Rewards_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass      = __CLASS__;
    protected        $_permission     = 'rewards';
    protected        $_modelClass     = 'Sellvana_Rewards_Model_Rule';
    protected        $_gridHref       = 'rewards';
    protected        $_gridTitle      = (('Rewards'));
    protected        $_recordName     = (('Rewards Rule'));
    protected        $_formTitleField = 'title';
    protected        $_mainTableAlias = 'rr';
    protected        $_navPath        = 'customer/rewards';
    protected        $_formLayoutName = '/rewards/form';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = [
            ['type' => 'row_select'],
            [
                'type'    => 'btn_group',
                'buttons' => [
                    ['name' => 'edit'],
                    ['name' => 'delete'],
                ]
            ],
            ['name' => 'id', 'label' => (('ID')), 'width' => 55, 'sorttype' => 'number'],
            ['name' => 'title', 'label' => (('Title')), 'width' => 250],
            ['name' => 'valid_from', 'label' => (('Valid From')), 'formatter' => 'date'],
            ['name' => 'valid_to', 'label' => (('Valid To')), 'formatter' => 'date'],
            ['name' => 'last_recalculated_at', 'label' => (('Last Calculated')), 'formatter' => 'date'],
            ['name' => 'create_at', 'label' => (('Created')), 'formatter' => 'date'],
            ['name' => 'update_at', 'label' => (('Updated')), 'formatter' => 'date'],
        ];
        $config['actions'] = [
            'edit'   => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'valid_from', 'type' => 'date-range'],
            ['field' => 'valid_to', 'type' => 'date-range'],
            ['field' => 'title', 'type' => 'text'],
        ];

        return $config;
    }

}
