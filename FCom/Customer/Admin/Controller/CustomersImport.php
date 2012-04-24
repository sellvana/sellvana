<?php

class FCom_Customer_Admin_Controller_CustomersImport extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'customers/import';

    public function action_index()
    {
        $this->layout('/customers/import');
    }

    public function action_config()
    {
        $view = $this->view('customer/import/config')->set(array(
            'dir' => FCom_Customer_Import::i()->getImportDir(),
            'file' => BRequest::i()->get('file'),
        ));
        $result['html'] = $view->render();
        BResponse::i()->json($result);
    }

    public function action_config__POST()
    {
        FCom_Customer_Import::i()->config(BRequest::i()->post('config'));
        BResponse::i()->redirect(BApp::href('customers/import/status'));
    }

    public function action_start()
    {
        FCom_Customer_Import::i()->run(BRequest::i()->post('config'));
        exit;
    }

    public function action_stop()
    {
        FCom_Customer_Import::i()->config(array('status'=>'stopped'), true);
        BResponse::i()->redirect(BApp::href('customers/import/status'));
    }

    public function action_status()
    {
        $view = BLayout::i()->view('customer/import/status')->set(array(
            'config' => FCom_Customer_Import::i()->config(),
        ));
        BResponse::i()->set($view->render());
    }
}