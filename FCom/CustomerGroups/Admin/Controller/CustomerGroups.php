<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Admin_Controller_CustomerGroups extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'customer-groups';
    protected $_modelClass = 'FCom_CustomerGroups_Model_Group';
    protected $_gridTitle = 'Customer Groups';
    protected $_recordName = 'Customer Group';
    protected $_mainTableAlias = 'cg';
    protected $_formViewName = 'customer-groups/form';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label'=>'ID', 'width'=>50, 'index' => 'cg.id'),
            array('name' => 'title', 'label' => 'Title', 'width' => 300, 'index' => 'cg.title', 'editable' => true, 'href' => BApp::href($this->_formViewName.'?id=:id')),
            array('name' => 'code', 'label' => 'Code', 'width' => 300, 'index' => 'cg.code', 'editable' => true),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                  'data'=> array('edit' => array('href' => BApp::href($this->_formViewName.'?id='), 'col' => 'id'), 'delete' => true)),
        );
        $config['actions'] = array(
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'code', 'type' => 'text'),
        );
        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $title = $m->id ? 'Edit Customer Group: '.$m->title : 'Create New Customer Group';
        $this->addTitle($title);
        $args['view']->set(array(
                                'title' => $title,
                           ));
    }

    public function addTitle($title = '')
    {
        /* @var $v BViewHead */
        $v = $this->view('head');
        if ($v) {
            $v->addTitle($title);
        }
    }
}
