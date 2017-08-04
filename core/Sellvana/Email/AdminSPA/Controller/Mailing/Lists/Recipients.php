<?php

class Sellvana_Email_AdminSPA_Controller_Mailing_Lists_Recipients extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    static protected $_modelClass = 'Sellvana_Email_Model_Mailing_ListRecipient';
    static protected $_modelName = 'list';
    static protected $_recordName = (('List'));

    public function getGridConfig()
    {
        $config = [
            static::ID => 'mailing_lists_recipients',
            static::DATA_URL => 'mailing/lists/recipients/grid_data?id=' . (int)$this->BRequest->get('id'),
            static::TITLE => (('Mailing List Recipients')),
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT],
                [static::NAME => 'id', static::LABEL => (('ID')), static::HIDDEN => true],
                [static::NAME => 'email', static::LABEL => (('Email'))],
                [static::NAME => 'firstname', static::LABEL => (('First Name'))],
                [static::NAME => 'lastname', static::LABEL => (('Last Name'))],
                [static::NAME => 'company', static::LABEL => (('Company'))],
                [static::NAME => 'create_at', static::LABEL => (('Created At'))],
                [static::NAME => 'update_at', static::LABEL => (('Updated At'))],
            ],
            static::FILTERS => true,
            static::PAGER => true,
            static::EXPORT => true,
            static::BULK_ACTIONS => [
                [static::NAME => 'remove', static::LABEL => (('Remove'))],
            ],
        ];

        return $config;
    }

    public function getGridOrm()
    {
        $orm = $this->{static::$_modelClass}->orm('r')
			->where('list_id', (int)$this->BRequest->get('id'))
            ->join('Sellvana_Email_Model_Mailing_Subscriber', ['r.subscriber_id', '=', 's.id'], 's')
            ->select(['r.*', 's.email', 's.firstname', 's.lastname', 's.company']);
        return $orm;
    }
}