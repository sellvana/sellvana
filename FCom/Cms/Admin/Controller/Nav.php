<?php

class FCom_Cms_Admin_Controller_Nav extends FCom_Admin_Controller_TreeAbstract
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'cms/nav';
    protected $_navModelClass = 'FCom_Cms_Model_Nav';
    protected $_treeLayoutName = '/cms/nav';
    protected $_formLayoutName = '/cms/nav/tree_form';
    protected $_formViewName = 'cms/nav-tree-form';

    protected function _prepareTreeForm($model)
    {
        $nodeTypes = array('content'=>'Text', 'cms_page'=>'CMS Page');
        BPubSub::i()->fire(__METHOD__, array('node_types'=>&$nodeTypes));
        $this->view('cms/nav-tree-form/main')->node_types = $nodeTypes;
    }
}