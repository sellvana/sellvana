<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Admin_Controller_CustomerGroups
    extends FCom_Admin_Controller_Abstract_GridForm
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
        $config['grid']['columns'] = array_replace_recursive($config['grid']['columns'],
            array(
                 'id'    => array('index' => 'cg.id'),
                 'title' => array('label' => 'Title', 'index' => 'cg.title'),
                 'code'  => array('label' => 'Code', 'index' => 'cg.code'),
            )
        );
        $config['custom']['dblClickHref'] = BApp::href('customer-groups/form/?id=');
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

    public function action_index()
    {
        $this->addTitle($this->_gridTitle);
        parent::action_index();
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