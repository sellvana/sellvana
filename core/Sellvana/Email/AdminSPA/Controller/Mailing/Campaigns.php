<?php

/**
 * Class Sellvana_Email_AdminSPA_Controller_Mailing_Campaigns
 *
 * @property Sellvana_Email_Model_Mailing_Campaign Sellvana_Email_Model_Mailing_Campaign
 * @property Sellvana_Email_Model_Mailing_List Sellvana_Email_Model_Mailing_List
 * @property Sellvana_Email_AdminSPA_Controller_Mailing_Campaigns_Recipients Sellvana_Email_AdminSPA_Controller_Mailing_Campaigns_Recipients
 */
class Sellvana_Email_AdminSPA_Controller_Mailing_Campaigns extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    static protected $_modelClass = 'Sellvana_Email_Model_Mailing_Campaign';
    static protected $_modelName = 'campaign';
    static protected $_recordName = (('Campaign'));

    public function getGridConfig()
    {
        $statusOptions = $this->Sellvana_Email_Model_Mailing_Campaign->fieldOptions('status');
        $listOptions = $this->Sellvana_Email_Model_Mailing_List->orm()->find_many_assoc('id', 'title');
        $config = [
            static::ID => 'mailing_campaigns',
            static::DATA_URL => 'mailing/campaigns/grid_data',
            static::TITLE => (('Mailing Campaigns')),
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT],
                [static::NAME => 'id', static::LABEL => (('ID')), static::HIDDEN => true],
                [static::NAME => 'title', static::LABEL => (('Title')), static::DATACELL_TEMPLATE => '<td><a :href="\'#/mailing/campaigns/form?id=\'+row.id">{{row.title}}</a></td>'],
                [static::NAME => 'list_id', static::LABEL => (('List')), static::OPTIONS => $listOptions],
                [static::NAME => 'status', static::LABEL => (('Status')), static::OPTIONS => $statusOptions],
                [static::NAME => 'sender_name', static::LABEL => (('Sender Name'))],
                [static::NAME => 'sender_email', static::LABEL => (('Sender Email'))],
                [static::NAME => 'subject', static::LABEL => (('Subject'))],
                [static::NAME => 'cnt_total', static::LABEL => (('# Total'))],
                [static::NAME => 'cnt_sent', static::LABEL => (('# Sent'))],
                [static::NAME => 'cnt_success', static::LABEL => (('# Success'))],
                [static::NAME => 'cnt_error', static::LABEL => (('# Error'))],
                [static::NAME => 'cnt_opened', static::LABEL => (('# Received'))],
                [static::NAME => 'cnt_clicked', static::LABEL => (('# Clicked'))],
                [static::NAME => 'cnt_unsub', static::LABEL => (('# Unsubscribed'))],
                [static::NAME => 'create_at', static::LABEL => (('Created At'))],
                [static::NAME => 'update_at', static::LABEL => (('Updated At'))],
            ],
            static::PAGE_ACTIONS => [
                [static::NAME => 'new', static::LABEL => (('Create New Campaign')), static::BUTTON_CLASS => 'button1', static::LINK => '/mailing/campaigns/form'],
            ],
            static::FILTERS => true,
            static::PAGER => true,
            static::EXPORT => true,
        ];

        return $config;
    }

    public function getFormData()
    {
        $campaignId = $this->BRequest->get('id');

        $statusOptions = $this->Sellvana_Email_Model_Mailing_Campaign->fieldOptions('status');
        $listOptions = $this->Sellvana_Email_Model_Mailing_List->orm()->find_many_assoc('id', 'title');

        if ($campaignId) {
            $campaign = $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId);
            if (!$campaign) {
                throw new BException('Campaign not found');
            }
        } else {
            $campaign = $this->Sellvana_Email_Model_Mailing_Campaign->create([
                'list_id' => key($listOptions),
            ]);
        }


        $result = [];
        $result[static::FORM]['campaign'] = $campaign->as_array();
        $result[static::FORM][static::CONFIG][static::TITLE] = $campaignId ? $campaign->get('title') : (('New Campaign'));
        $result[static::FORM][static::CONFIG][static::TABS] = '/mailing/campaigns/form';
        $result[static::FORM][static::CONFIG][static::FIELDS] = [
            static::DEFAULT_FIELD => [static::MODEL => 'campaign', static::TAB => 'main'],
            [static::NAME => 'title', static::LABEL => (('Title')), static::REQUIRED => true],
            [static::NAME => 'list_id', static::LABEL => (('List')), static::REQUIRED => true, static::TYPE => 'select2', static::OPTIONS => $listOptions],
            [static::NAME => 'sender_name', static::LABEL => (('Sender Name')), static::REQUIRED => true],
            [static::NAME => 'sender_email', static::LABEL => (('Sender Email')), static::REQUIRED => true, static::INPUT_TYPE => 'email'],
            [static::NAME => 'subject', static::LABEL => (('Subject')), static::REQUIRED => true],
            [static::NAME => 'template_html', static::LABEL => (('Template HTML')), static::REQUIRED => true, static::TYPE => 'textarea'],
        ];
        if ($campaign->id()) {
            #$result[static::FORM][static::CONFIG][static::FIELDS][] = [static::NAME => 'status', static::LABEL => (('Status')), static::OPTIONS => $statusOptions];
        }
        $result[static::FORM]['status_options'] = $statusOptions;

        $result[static::FORM][static::CONFIG][static::PAGE_ACTIONS] = $this->getDefaultFormPageActions();

        $result[static::FORM]['recipients_grid'][static::CONFIG] =
            $this->Sellvana_Email_AdminSPA_Controller_Mailing_Campaigns_Recipients->getNormalizedGridConfig();

        return $result;
    }

    public function action_import_from_list__POST()
    {
        try {
            $campaignId = $this->BRequest->get('campaign_id');
            $listId = $this->BRequest->get('list_id');
            $campaign = $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId);
            if (!$campaign) {
                throw new BException(('Invalid campaign ID'));
            }
            $campaign->importRecipientsFromList($listId);
            $this->addMessage('List imported successfully');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond();
    }

    public function action_start__POST()
    {
        try {
            $this->BResponse->startLongResponse(false);
            $campaignId = $this->BRequest->request('id');
            $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId)->start();
            $this->ok();
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond();
    }

    public function action_stop__POST()
    {
        try {
            $campaignId = $this->BRequest->request('id');
            $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId)->stop();
            $this->ok()->addMessage('Campaign is stopped successfully', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond();
    }

    public function action_pause__POST()
    {
        try {
            $campaignId = $this->BRequest->request('id');
            $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId)->pause();
            $this->ok()->addMessage('Campaign is paused successfully', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond();

    }

    public function action_resume__POST()
    {
        try {
            $campaignId = $this->BRequest->request('id');
            $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId)->resume();
            $this->ok()->addMessage('Campaign is resumed successfully', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond();
    }

    public function action_progress()
    {
        $result = [];
        try {
            $campaignId = $this->BRequest->request('id');
            $data = $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId)->as_array();
            unset($data['data_serialized'], $data['template_html']);
            $result[static::FORM]['campaign'] = $data;
            $this->ok();
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }
}