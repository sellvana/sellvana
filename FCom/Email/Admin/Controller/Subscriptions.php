<?php

class FCom_Email_Admin_Controller_Subscriptions extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'subscriptions';
    protected $_modelClass = 'FCom_Email_Model_Pref';
    protected $_gridTitle = 'Subscriptions';
    protected $_recordName = 'Subscription';
    protected $_mainTableAlias = 'e';

    public function gridConfig()
    {
        $config            = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID', 'index' => 'e.id'),
            array('name' => 'email', 'label' => 'Email', 'index' => 'e.email'),
            array('name'          => 'unsub_all',
                  'label'         => 'Un-subscribe all',
                  'index'         => 'e.unsub_all',
                  'editable'      => true,
                  'mass-editable' => true,
                  'options'       => array('1' => 'Yes', '0' => 'No'),
                  'editor'        => 'select'
            ),
            array('name'          => 'sub_newsletter',
                  'label'         => 'Subscribe newsletter',
                  'index'         => 'e.sub_newsletter',
                  'editable'      => true,
                  'mass-editable' => true,
                  'options'       => array('1' => 'Yes', '0' => 'No'),
                  'editor'        => 'select'
            ),
            array('name' => 'create_at', 'label' => 'Created', 'index' => 'e.create_at'),
            array(
                'name'     => '_actions',
                'label'    => 'Actions',
                'sortable' => false,
                'data'     => array('edit' => array('href' => BApp::href('subscriptions/form?id='), 'col' => 'id'), 'delete' => true)
            ),
        );
        $config['actions'] = array(
            'export' => true,
            'edit'   => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'email', 'type' => 'text'),
            array('field' => 'sub_newsletter', 'type' => 'select'),
        );
        return $config;
    }
}