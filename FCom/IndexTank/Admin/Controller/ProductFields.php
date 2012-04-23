<?php

class FCom_IndexTank_Admin_Controller_ProductFields extends FCom_Admin_Controller_Abstract_GridForm
{
    //protected $_permission = 'cms/pages';
    protected $_gridHref = 'indextank/product_fields';
    protected $_gridLayoutName = '/indextank/product_fields';
    protected $_formLayoutName = '/indextank/product_fields/form';
    protected $_formViewName = 'indextank/product_fields-form';
    protected $_modelClassName = 'FCom_IndexTank_Model_ProductFields';
    protected $_mainTableAlias = 'pf';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] += array(
            'field_nice_name' => array('label'=>'Name', 'editable'=>true, 'formatter'=>'showlink', 'formatoptions'=>array(
                'baseLinkUrl' => BApp::href('indextank/product_fields/form'), 'idName' => 'id',
            )),
            'search' => array('label'=>'Search'),
            'facets' => array('label'=>'Facets'),
            'sorting' => array('label'=>'Sorting'),
            'priority' => array('label'=>'Priority'),
            'show' => array('label'=>'Display as'),
            'filter' => array('label'=>'Filter type'),
        );
        return $config;
    }

    public function action_form__POST() {
        $id = BRequest::i()->params('id', true);
        $model = BRequest::i()->post('model');

        if($id){

        } else {
            //BSession::i()->addMessage('Id not found', 'error', 'admin');
            //BResponse::i()->redirect(BApp::href('indextank/product_fields/form/?id='.$id));
        }

        parent::action_form__POST();
    }

}