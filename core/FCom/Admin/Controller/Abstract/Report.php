<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Admin_Controller_Abstract_Report extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_gridPageViewName = 'admin/report';
    protected $_defaultGridLayoutName = 'default_report';
    protected $_gridLayoutName = 'default_report';

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $view = $args['page_view'];
        $view->set('actions', []);
    }

}