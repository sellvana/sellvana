<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Customer_Admin_Controller_CustomersImport
 *
 * @property FCom_Admin_Controller_MediaLibrary $FCom_Admin_Controller_MediaLibrary
 * @property FCom_Customer_Import $FCom_Customer_Import
 */
class FCom_Customer_Admin_Controller_CustomersImport extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'customers/import';

    public function customerFilesGridConfig()
    {
        return $this->FCom_Admin_Controller_MediaLibrary->gridConfig([
            'id' => 'import_files',
            'folder' => 'storage/import/customers',
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
        $this->layout('/customers/import');
    }

    public function action_config()
    {
        $view = $this->view('customer/import/config')->set([
            'dir' => $this->FCom_Customer_Import->getImportDir(),
            'file' => $this->BRequest->get('file'),
        ]);
        $result['html'] = $view->render();
        $this->BResponse->json($result);
    }

    public function action_config__POST()
    {
        $this->FCom_Customer_Import->config($this->BRequest->post('config'));
        $this->BResponse->redirect('customers/import/status');
    }

    public function action_start()
    {
        $this->FCom_Customer_Import->run();
        $this->BResponse->redirect('customers/import/status');
    }

    public function action_stop()
    {
        $this->FCom_Customer_Import->config(['status' => 'stopped'], true);
        $this->BResponse->redirect('customers/import/status');
    }

    public function action_status()
    {
        $s = $this->BRequest->request('start');
        $view = $this->BLayout->view('customer/import/status')->set(['start' => $s]);
        $this->BResponse->set($view->render());
    }
}
