<?php

class Sellvana_Email_AdminSPA_Controller_Mailing_Lists extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    static protected $_modelClass = 'Sellvana_Email_Model_Mailing_List';
    static protected $_modelName = 'list';
    static protected $_recordName = (('List'));

    public function getGridConfig()
    {
        $config = [
            'id' => 'mailing_lists',
            'data_url' => 'mailing/lists/grid_data',
            'title' => (('Mailing Lists')),
            'columns' => [
                ['type' => 'row-select'],
                ['name' => 'id', 'label' => (('ID')), 'hidden' => true],
                ['name' => 'title', 'label' => (('Title')), 'datacell_template' => '<td><a :href="\'#/mailing/lists/form?id=\'+row.id">{{row.title}}</a></td>'],
                ['name' => 'create_at', 'label' => (('Created At'))],
                ['name' => 'update_at', 'label' => (('Updated At'))],
            ],
            'page_actions' => [
                ['name' => 'new', 'label' => (('Create New Mailing List')), 'button_class' => 'button1', 'link' => '/mailing/lists/form'],
            ],
            'filters' => true,
            'pager' => true,
            'export' => true,
        ];

        return $config;
    }

    public function getFormData()
    {
        $listId = $this->BRequest->get('id');

        if ($listId) {
            $list = $this->Sellvana_Email_Model_Mailing_List->load($listId);
            if (!$list) {
                throw new BException('List not found');
            }
        } else {
            $list = $this->Sellvana_Email_Model_Mailing_List->create();
        }

        $result = [];
        $result['form']['list'] = $list->as_array();
        $result['form']['config']['title'] = $listId ? $list->get('title') : (('New List'));
        $result['form']['config']['tabs'] = '/mailing/lists/form';
        $result['form']['config']['fields'] = [
            'default' => ['model' => 'list', 'tab' => 'main'],
            ['name' => 'title', 'label' => (('Title')), 'required' => true],
            ['name' => 'import_recipients', 'label' => (('Paste Here')), 'type' => 'textarea', 'tab' => 'import',
                'notes' => (('Email, First Name, Last Name, Company'))],
        ];
        if ($list->id()) {
            #$result['form']['config']['fields'][] = ['name' => 'status', 'label' => (('Status')), 'options' => $statusOptions];
        }
        $result['form']['config']['page_actions'] = $this->getDefaultFormPageActions();

        $result['form']['recipients_grid']['config'] =
            $this->Sellvana_Email_AdminSPA_Controller_Mailing_Lists_Recipients->getNormalizedGridConfig();

        return $result;
    }

    public function onAfterFormDataPost($args)
    {
        parent::onAfterFormDataPost($args);

        if (!empty($args['data']['import_recipients'])) {
            $this->Sellvana_Email_Model_Mailing_ListRecipient
                ->importAsTextCsv($args['model']->id(), $args['data']['import_recipients']);
        }

        return $this;
    }
}