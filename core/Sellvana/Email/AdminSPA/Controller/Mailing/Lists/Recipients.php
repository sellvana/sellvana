<?php

class Sellvana_Email_AdminSPA_Controller_Mailing_Lists_Recipients extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    static protected $_modelClass = 'Sellvana_Email_Model_Mailing_ListRecipient';
    static protected $_modelName = 'list';
    static protected $_recordName = (('List'));

    public function getGridConfig()
    {
        $config = [
            'id' => 'mailing_lists_recipients',
            'data_url' => 'mailing/lists/recipients/grid_data',
            'title' => (('Mailing List Recipients')),
            'columns' => [
                ['type' => 'row-select'],
                ['name' => 'id', 'label' => (('ID')), 'hidden' => true],
                ['name' => 'email', 'label' => (('Email'))],
                ['name' => 'firstname', 'label' => (('First Name'))],
                ['name' => 'lastname', 'label' => (('Last Name'))],
                ['name' => 'company', 'label' => (('Company'))],
                ['name' => 'create_at', 'label' => (('Created At'))],
                ['name' => 'update_at', 'label' => (('Updated At'))],
            ],
            'filters' => true,
            'pager' => true,
            'export' => true,
            'bulk_actions' => [
                ['name' => 'remove', 'label' => (('Remove'))],
            ],
        ];

        return $config;
    }

    public function getGridOrm()
    {
        $orm = $this->{static::$_modelClass}->orm('r')
            ->join('Sellvana_Email_Model_Mailing_Subscriber', ['r.subscriber_id', '=', 's.id'], 's')
            ->select(['r.*', 's.email', 's.firstname', 's.lastname', 's.company']);
        return $orm;
    }
}