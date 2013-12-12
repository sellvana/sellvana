<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Admin_Controller_CustomerGroups extends FCom_Admin_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'customer-groups';
    protected $_modelClass = 'FCom_CustomerGroups_Model_Group';
    protected $_gridTitle = 'Customer Groups';
    protected $_recordName = 'Customer Group';
    protected $_mainTableAlias = 'cg';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label'=>'ID', 'width'=>50, 'index' => 'cg.id'),
            array('name' => 'title', 'label' => 'Title', 'width' => 300, 'index' => 'cg.title', 'editable' => true, 'addable' => true,
                  'validation' => array('required' => true)),
            array('name' => 'code', 'label' => 'Code', 'width' => 300, 'index' => 'cg.code', 'editable' => true, 'addable' => true,
                  'validation' => array('required' => true, 'unique' => BApp::href('customer-groups/unique'))),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                  'data'=> array('edit' => true, 'delete' => true)),
        );
        $config['actions'] = array(
            'new' => array('caption' => 'Add New Customer Group', 'modal' => true),
            'edit' => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'code', 'type' => 'text'),
        );
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(array( 'actions' => array( 'new' => '')));
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $title = $m->id ? 'Edit Customer Group: '.$m->title : 'Create New Customer Group';
        $this->addTitle($title);
        $args['view']->set(array('title' => $title));
    }

    public function addTitle($title = '')
    {
        /* @var $v BViewHead */
        $v = $this->view('head');
        if ($v) {
            $v->addTitle($title);
        }
    }

    public function formPostAfter($args)
    {
        $data = $args['data'];
        $model = $args['model'];
        if (!empty($data['removed_ids'])) {
            $customer_ids = explode(",", $data['removed_ids']);
            foreach ($customer_ids as $id) {
                $customer = FCom_Customer_Model_Customer::i()->load($id);
                if ($customer) {
                    $customer->customer_group = null;
                    $customer->save();
                }
            }
        }
        if (!empty($data['rows'])) {
            $customer_ids = explode(",", $data['rows']);
            foreach ($customer_ids as $id) {
                $customer = FCom_Customer_Model_Customer::i()->load($id);
                if ($customer) {
                    $customer->customer_group = $model->id;
                    $customer->save();
                }
            }
        }
    }

    public function action_unique__POST()
    {
        $post = BRequest::i()->post();
        $data = each($post);
        $rows = BDb::many_as_array(FCom_CustomerGroups_Model_Group::i()->orm()->where($data['key'], $data['value'])->find_many());
        BResponse::i()->json(array( 'unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])));
    }
}
