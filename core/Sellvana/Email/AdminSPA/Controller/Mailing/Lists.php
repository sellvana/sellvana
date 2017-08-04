<?php

class Sellvana_Email_AdminSPA_Controller_Mailing_Lists extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    static protected $_modelClass = 'Sellvana_Email_Model_Mailing_List';
    static protected $_modelName = 'list';
    static protected $_recordName = (('List'));

    public function getGridConfig()
    {
        $config = [
            static::ID => 'mailing_lists',
            static::DATA_URL => 'mailing/lists/grid_data',
            static::TITLE => (('Mailing Lists')),
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT],
                [static::NAME => 'id', static::LABEL => (('ID')), static::HIDDEN => true],
                [static::NAME => 'title', static::LABEL => (('Title')), static::DATACELL_TEMPLATE => '<td><a :href="\'#/mailing/lists/form?id=\'+row.id">{{row.title}}</a></td>'],
                [static::NAME => 'create_at', static::LABEL => (('Created At'))],
                [static::NAME => 'update_at', static::LABEL => (('Updated At'))],
            ],
            static::PAGE_ACTIONS => [
                [static::NAME => 'new', static::LABEL => (('Create New Mailing List')), static::BUTTON_CLASS => 'button1', static::LINK => '/mailing/lists/form'],
            ],
            static::FILTERS => true,
            static::PAGER => true,
            static::EXPORT => true,
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
        $result[static::FORM]['list'] = $list->as_array();
        $result[static::FORM][static::CONFIG][static::TITLE] = $listId ? $list->get('title') : (('New List'));
        $result[static::FORM][static::CONFIG][static::TABS] = '/mailing/lists/form';
        $result[static::FORM][static::CONFIG][static::FIELDS] = [
            static::DEFAULT_FIELD => [static::MODEL => 'list', static::TAB => 'main'],
            [static::NAME => 'title', static::LABEL => (('Title')), static::REQUIRED => true],
            [static::NAME => 'import_recipients', static::LABEL => (('Paste Here')), static::TYPE => 'textarea', static::TAB => 'import',
                static::NOTES => (('Email, First Name, Last Name, Company'))],
        ];
        if ($list->id()) {
            #$result[static::FORM][static::CONFIG][static::FIELDS][] = [static::NAME => 'status', static::LABEL => (('Status')), static::OPTIONS => $statusOptions];
        }
        $result[static::FORM][static::CONFIG][static::PAGE_ACTIONS] = $this->getDefaultFormPageActions();

        $result[static::FORM]['recipients_grid'][static::CONFIG] =
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