<?php

class FCom_Catalog_Admin_Controller_Categories extends FCom_Admin_Controller_Abstract_TreeForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'catalog/categories';
    protected $_navModelClass = 'FCom_Catalog_Model_Category';
    protected $_treeLayoutName = '/catalog/categories';
    protected $_formLayoutName = '/catalog/categories/tree_form';
    protected $_formViewName = 'catalog/categories-tree-form';

}