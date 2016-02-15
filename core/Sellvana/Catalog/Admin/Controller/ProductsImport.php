<?php

/**
 * Class Sellvana_Catalog_Admin_Controller_ProductsImport
 * @property FCom_Admin_Controller_MediaLibrary $FCom_Admin_Controller_MediaLibrary
 * @property Sellvana_Catalog_ProductsImport $Sellvana_Catalog_ProductsImport
 */
class Sellvana_Catalog_Admin_Controller_ProductsImport extends FCom_Admin_Controller_Abstract
{
    //protected $_permission = 'catalog/products/import';
    protected $_permission = 'catalog/products';

    public function getImportFilesGridConfig()
    {
        return $this->FCom_Admin_Controller_MediaLibrary->gridConfig([
            'id' => 'import_files',
            'folder' => 'storage/import/products',
            'config' => [
                'grid' => [
                    'multiselect' => false,
                    'autowidth' => false,
                    'width' => 600,
                    'height' => 300,
                ],
            ],
        ]);
    }

    public function action_index()
    {
        $this->layout('/catalog/products/import');
    }

    public function action_config()
    {
        $view = $this->view('catalog/products/import/config')->set([
            'dir' => $this->Sellvana_Catalog_ProductsImport->getImportDir(),
            'file' => $this->BRequest->get('file'),
        ]);
        $result['html'] = $view->render();
        $this->BResponse->json($result);
    }

    public function action_config__POST()
    {
        $this->Sellvana_Catalog_ProductsImport->config($this->BRequest->post('config'));
        $this->BResponse->redirect('catalog/products/import/status');
    }

    public function action_start__POST()
    {
        $this->Sellvana_Catalog_ProductsImport->run();
        $this->BResponse->redirect('catalog/products/import/status');
    }

    public function action_stop__POST()
    {
        $this->Sellvana_Catalog_ProductsImport->config(['status' => 'stopped'], true);
        $this->BResponse->redirect('catalog/products/import/status');
    }

    public function action_status()
    {
        $s = $this->BRequest->request('start');
        $view = $this->BLayout->getView('catalog/products/import/status')->set(['start' => $s]);
        $this->BResponse->set($view->render());
    }
}
