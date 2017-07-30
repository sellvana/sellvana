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
            'id' => 'mailing_campaigns',
            'data_url' => 'mailing/campaigns/grid_data',
            'title' => (('Mailing Campaigns')),
            'columns' => [
                ['type' => 'row-select'],
                ['name' => 'id', 'label' => (('ID')), 'hidden' => true],
                ['name' => 'title', 'label' => (('Title')), 'datacell_template' => '<td><a :href="\'#/mailing/campaigns/form?id=\'+row.id">{{row.title}}</a></td>'],
                ['name' => 'list_id', 'label' => (('List')), 'options' => $listOptions],
                ['name' => 'status', 'label' => (('Status')), 'options' => $statusOptions],
                ['name' => 'sender_name', 'label' => (('Sender Name'))],
                ['name' => 'sender_email', 'label' => (('Sender Email'))],
                ['name' => 'subject', 'label' => (('Subject'))],
                ['name' => 'cnt_total', 'label' => (('# Total'))],
                ['name' => 'cnt_sent', 'label' => (('# Sent'))],
                ['name' => 'cnt_success', 'label' => (('# Success'))],
                ['name' => 'cnt_error', 'label' => (('# Error'))],
                ['name' => 'cnt_opened', 'label' => (('# Received'))],
                ['name' => 'cnt_clicked', 'label' => (('# Clicked'))],
                ['name' => 'cnt_unsub', 'label' => (('# Unsubscribed'))],
                ['name' => 'create_at', 'label' => (('Created At'))],
                ['name' => 'update_at', 'label' => (('Updated At'))],
            ],
            'page_actions' => [
                ['name' => 'new', 'label' => (('Create New Campaign')), 'button_class' => 'button1', 'link' => '/mailing/campaigns/form'],
            ],
            'filters' => true,
            'pager' => true,
            'export' => true,
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
        $result['form']['campaign'] = $campaign->as_array();
        $result['form']['config']['title'] = $campaignId ? $campaign->get('title') : (('New Campaign'));
        $result['form']['config']['tabs'] = '/mailing/campaigns/form';
        $result['form']['config']['fields'] = [
            'default' => ['model' => 'campaign', 'tab' => 'main'],
            ['name' => 'title', 'label' => (('Title')), 'required' => true],
            ['name' => 'list_id', 'label' => (('List')), 'required' => true, 'type' => 'select2', 'options' => $listOptions],
            ['name' => 'sender_name', 'label' => (('Sender Name')), 'required' => true],
            ['name' => 'sender_email', 'label' => (('Sender Email')), 'required' => true, 'input_type' => 'email'],
            ['name' => 'subject', 'label' => (('Subject')), 'required' => true],
            ['name' => 'template_html', 'label' => (('Template HTML')), 'required' => true, 'type' => 'textarea'],
        ];
        if ($campaign->id()) {
            #$result['form']['config']['fields'][] = ['name' => 'status', 'label' => (('Status')), 'options' => $statusOptions];
        }
        $result['form']['status_options'] = $statusOptions;

        $result['form']['config']['page_actions'] = $this->getDefaultFormPageActions();

        $result['form']['recipients_grid']['config'] =
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
            $result['form']['campaign'] = $data;
            $this->ok();
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }
}