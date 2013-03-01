<?php

class FCom_Catalog_Admin_Controller_ProductsImport extends FCom_Admin_Controller_Abstract
{
    //protected $_permission = 'catalog/products/import';

    public function action_index()
    {
        $this->layout('/catalog/products/import');
    }

    public function action_config()
    {
        $view = $this->view('catalog/products/import/config')->set(array(
            'dir' => FCom_Catalog_ProductsImport::i()->getImportDir(),
            'file' => BRequest::i()->get('file'),
        ));
        $result['html'] = $view->render();
        BResponse::i()->json($result);
    }

    public function action_config__POST()
    {
        FCom_Catalog_ProductsImport::i()->config(BRequest::i()->post('config'));
        BResponse::i()->redirect(BApp::href('catalog/products/import/status'));
    }

    public function action_start()
    {
        FCom_Catalog_ProductsImport::i()->run();
        exit;
    }

    public function action_stop()
    {
        FCom_Catalog_ProductsImport::i()->config(array('status'=>'stopped'), true);
        BResponse::i()->redirect(BApp::href('catalog/products/import/status'));
    }

    public function action_status()
    {
        BResponse::i()->set(BLayout::i()->view('catalog/products/import/status')->render());
    }
}