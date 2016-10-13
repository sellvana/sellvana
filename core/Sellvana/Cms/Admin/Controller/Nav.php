<?php

class Sellvana_Cms_Admin_Controller_Nav extends FCom_Admin_Controller_Abstract_TreeForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'cms/nav';
    protected $_navModelClass = 'Sellvana_Cms_Model_Nav';
    protected $_treeLayoutName = '/cms/nav';
    protected $_formLayoutName = '/cms/nav/tree_form';
    protected $_formViewName = 'cms/nav-tree-form';

    public $formId = 'cms_tree_form';

    /**
     * @param $model
     */
    protected function _prepareTreeForm($model)
    {
        $nodeTypes = ['content' => 'Text', 'cms_page' => 'CMS Page'];
        $this->BEvents->fire(__METHOD__, ['node_types' => &$nodeTypes]);
        $this->view('cms/nav-tree-form/main')->set('node_types', $nodeTypes);
    }
}
