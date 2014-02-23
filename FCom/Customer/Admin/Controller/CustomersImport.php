<?php

class FCom_Customer_Admin_Controller_CustomersImport extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'customers/import';

    public function customerFilesGridConfig()
    {
        return FCom_Admin_Controller_MediaLibrary::i()->gridConfig(array(
            'id' => 'import_files',
            'folder' => 'storage/import/customers',
            'config' => array(
                'grid' => array(
                    'multiselect'=>false,
                    'autowidth'=>false,
                    'width'=>600,
                    'height'=>300,
                ),
            ),
        ));
    }

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
        BResponse::i()->redirect('customers/import/status');
    }

    public function action_start()
    {
        FCom_Customer_Import::i()->run();
        BResponse::i()->redirect('customers/import/status');
    }

    public function action_stop()
    {
        FCom_Customer_Import::i()->config(array('status'=>'stopped'), true);
        BResponse::i()->redirect('customers/import/status');
    }

    public function action_status()
    {
        $s = BRequest::i()->request('start');
        $view = BLayout::i()->view('customer/import/status')->set(array('start'=>$s));
        BResponse::i()->set($view->render());
    }
}
