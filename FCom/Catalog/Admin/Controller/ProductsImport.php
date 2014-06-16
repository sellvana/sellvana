<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Catalog_Admin_Controller_ProductsImport extends FCom_Admin_Controller_Abstract
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
        $view = $this->BLayout->view('catalog/products/import');
        if($view){
            $this->setUploadConfig($view);
        }
    }

    public function action_config()
    {
        $view = $this->view('catalog/products/import/config')->set([
            'dir' => $this->FCom_Catalog_ProductsImport->getImportDir(),
            'file' => $this->BRequest->get('file'),
        ]);
        $result['html'] = $view->render();
        $this->BResponse->json($result);
    }

    public function action_config__POST()
    {
        $this->FCom_Catalog_ProductsImport->config($this->BRequest->post('config'));
        $this->BResponse->redirect('catalog/products/import/status');
    }

    public function action_start()
    {
        $this->FCom_Catalog_ProductsImport->run();
        $this->BResponse->redirect('catalog/products/import/status');
        exit;
    }

    public function action_stop()
    {
        $this->FCom_Catalog_ProductsImport->config(['status' => 'stopped'], true);
        $this->BResponse->redirect('catalog/products/import/status');
    }

    public function action_status()
    {
        $s = $this->BRequest->request('start');
        $view = $this->BLayout->view('catalog/products/import/status')->set(['start' => $s]);
        $this->BResponse->set($view->render());
    }

    /**
     * @param BView $view
     */
    protected function setUploadConfig($view)
    {
        $productImport         = $this->BConfig->get('uploads/product-import');
        $productImport['type'] = 'product-import';
        if (isset($productImport['filetype'])) {
            $productImport['filetype'] = '(\.|\\/)(' . str_replace([','], '|', $productImport['filetype']) . ')$/i';
        }

        if (isset($productImport['permission'])) {
            $canUpload                   = $this->FCom_Admin_Model_User->sessionUser()
                                                                       ->getPermission($productImport['permission']);
            $productImport['can_upload'] = $canUpload;
        }
        $view->set('upload_config', $productImport);
    }
}
