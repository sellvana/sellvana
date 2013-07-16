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
        $gridId = 'customer-groups';
        $config = array(
            'grid' => array(
                'id' => $gridId,
                'caption' => BLocale::_('Customer Groups'),
                'data_url' => BApp::href('customer-groups/grid_data'),
                'edit_url' => BApp::href('customer-groups/grid_data/?id='),
                'columns' => array(
                    'id' => array('label'=>'ID', 'width'=>30, 'index' => 'cg.id'),
                    'title' => array('label' => 'Title', 'width' => 300, 'index' => 'cg.title', 'editable' => true,
                                     'href' => BApp::href('customer-groups/form/?id=<%=id%>')),
                    'code' => array('label' => 'Code', 'width' => 300, 'index' => 'cg.code', 'editable' => true),
                ),
                'multiselect' => true,
            ),
            'navGrid' => array('add'=>true, 'addtext'=>'New', 'addtitle'=>'Create new Field', 'edit'=>true, 'del'=>true),
            'custom' => array('personalize'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),

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