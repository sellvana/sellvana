<?php

class FCom_Admin_Controller_Modules extends FCom_Admin_Controller_Abstract
{
    public function gridConfig()
    {
        $baseHref = BApp::m('FCom_Admin')->baseHref();
        $linkConf = array('formatter'=>'showlink', 'formatoptions'=>array('baseLinkUrl'=>$baseHref.'/users/form/'));
        $config = array(
            'grid' => array(
                'id'            => 'users',
                'url'           => $baseHref.'/users/grid_data',
                'editurl'       => $baseHref.'/users/grid_data',
                'colModel'      => array(
                    array('name'=>'id', 'label'=>'ID', 'index'=>'u.id', 'width'=>55),
                    array('name'=>'username', 'label'=>'User Name', 'width'=>100) + $linkConf,
                    array('name'=>'email', 'label'=>'Email', 'width'=>150) + $linkConf,
                    array('name'=>'firstname', 'label'=>'First Name', 'width'=>150),
                    array('name'=>'lastname', 'label'=>'First Name', 'width'=>150),
                    array('name'=>'status', 'label'=>'Status', 'width'=>100,
                        'options'=>FCom_Admin_Model_User::i()->fieldOptions('status')),
                    array('name'=>'last_login', 'label'=>'Last Login', 'formatter'=>'date', 'width'=>100),
                ),
                'sortname'      => 'u.id',
                'sortorder'     => 'asc',
                'multiselect'   => true,
            ),
            'navGrid' => array(),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true),
            array('navButtonAdd', 'caption' => 'Columns', 'title' => 'Reorder Columns', 'onClickButton' => 'function() {
                jQuery("#grid-users").jqGrid("columnChooser");
            }'),
        );
        BPubSub::i()->fire('FCom_Admin_Controller_Users::gridConfig', array('config'=>&$config));
        return $config;
    }

    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid')->set('config', $this->gridConfig());
        BPubSub::i()->fire('FCom_Admin_Controller_Modlues::action_index', array('grid'=>$grid));
        $this->layout('/modules');
    }
}