<?php

class Sellvana_Email_AdminSPA_Controller_Mailing_Campaigns_Recipients extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    static protected $_modelClass = 'Sellvana_Email_Model_Mailing_CampaignRecipient';
    static protected $_modelName = 'list';
    static protected $_recordName = (('List'));

    public function getGridConfig()
    {
        $statusOptions = $this->Sellvana_Email_Model_Mailing_CampaignRecipient->fieldOptions('status');

        $config = [
            static::ID => 'mailing_campaigns_recipients',
            static::DATA_URL => 'mailing/campaigns/recipients/grid_data',
            static::TITLE => (('Mailing Campaign Recipients')),
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT],
                [static::NAME => 'id', static::LABEL => (('ID')), static::HIDDEN => true],
                [static::NAME => 'email', static::LABEL => (('Email'))],
                [static::NAME => 'firstname', static::LABEL => (('First Name'))],
                [static::NAME => 'lastname', static::LABEL => (('Last Name'))],
                [static::NAME => 'company', static::LABEL => (('Company'))],
                [static::NAME => 'status', static::LABEL => (('Status')), static::OPTIONS => $statusOptions],
                [static::NAME => 'create_at', static::LABEL => (('Created At'))],
                [static::NAME => 'update_at', static::LABEL => (('Updated At'))],
            ],
            static::FILTERS => true,
            static::PAGER => true,
            static::EXPORT => true,
//            static::PANEL_ACTIONS => [
//                [static::NAME => 'import_from_list', static::LABEL => (('Import From List')), static::BUTTON_CLASS => 'button2'],
//            ],
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