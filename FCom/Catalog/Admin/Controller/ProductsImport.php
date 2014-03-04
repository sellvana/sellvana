<?php

class FCom_Catalog_Admin_Controller_ProductsImport extends FCom_Admin_Controller_Abstract
{
    //protected $_permission = 'catalog/products/import';
    protected $_permission = 'catalog/products';

    public function getImportFilesGridConfig()
    {
        return FCom_Admin_Controller_MediaLibrary::i()->gridConfig(array(
            'id' => 'import_files',
            'folder' => 'storage/import/products',
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
        BResponse::i()->redirect('catalog/products/import/status');
    }

    public function action_start()
    {
        FCom_Catalog_ProductsImport::i()->run();
        BResponse::i()->redirect('catalog/products/import/status');
        exit;
    }

    public function action_stop()
    {
        FCom_Catalog_ProductsImport::i()->config(array('status'=>'stopped'), true);
        BResponse::i()->redirect('catalog/products/import/status');
    }

    public function action_status()
    {
        $s = BRequest::i()->request('start');
        $view = BLayout::i()->view('catalog/products/import/status')->set(array('start'=>$s));
        BResponse::i()->set($view->render());
    }
}
