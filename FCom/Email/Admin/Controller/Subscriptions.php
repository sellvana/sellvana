<?php

class FCom_Email_Admin_Controller_Subscriptions extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'subscriptions';
    protected $_modelClass = 'FCom_Email_Model_Pref';
    protected $_gridTitle = 'Subscriptions';
    protected $_recordName = 'Subscription';
    protected $_mainTableAlias = 'e';
    protected $_permission = 'subscriptions';

    public function gridConfig()
    {
        $config            = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID', 'index' => 'e.id'),
            array('name' => 'email', 'label' => 'Email', 'index' => 'e.email', 'addable' => true, 'editable' => true,
                  'validation' => array('required' => true, 'unique' => BApp::href('subscriptions/unique'))),
            array('name' => 'unsub_all', 'label' => 'Un-subscribe all', 'index' => 'e.unsub_all', 'addable' => true, 'editable' => true,
                  'mass-editable' => true, 'options' => array('1' => 'Yes', '0' => 'No'), 'editor' => 'select'),
            array('name' => 'sub_newsletter', 'label' => 'Subscribe newsletter', 'index' => 'e.sub_newsletter', 'addable' => true,
                  'editable' => true, 'mass-editable' => true, 'options' => array('1' => 'Yes', '0' => 'No'), 'editor' => 'select'),
            array('name' => 'create_at', 'label' => 'Created', 'index' => 'e.create_at'),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'data' => array('edit' => true, 'delete' => true)),
        );
        $config['actions'] = array(
            'new' => array('caption' => 'New Email Subscription', 'modal' => true),
            'export' => true,
            'edit'   => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'email', 'type' => 'text'),
            array('field' => 'sub_newsletter', 'type' => 'multiselect'),
        );
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(array( 'actions' => array( 'new' => '')));
    }

    public function action_unique__POST()
    {
        $post = BRequest::i()->post();
        $data = each($post);
        $rows = BDb::many_as_array(FCom_Email_Model_Pref::i()->orm()->where($data['key'], $data['value'])->find_many());
        BResponse::i()->json(array( 'unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])));
    }
}
