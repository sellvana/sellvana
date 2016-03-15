<?php

/**
 * Class Sellvana_SampleData_Admin_Controller
 *
 * @property Sellvana_SampleData_Admin $Sellvana_SampleData_Admin
 */
class Sellvana_SampleData_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'sample_data';
    public function action_load()
    {
        $xhr    = $this->BRequest->xhr();
        $msg    = "Sample products not imported.";
        $status = 'error';

        try {
            $this->BResponse->startLongResponse();
            $this->BConfig->set('db/logging', 0);

            $this->Sellvana_SampleData_Admin->loadProducts();
            $msg    = $this->_('Sample products imported');
            $status = 'success';
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $msg    = $e->getMessage();
            $status = 'error';
        }
        if (!$xhr) {
            $this->message($msg, $status);
            $this->BResponse->redirect('settings?tab=other');
        } else {
            echo $msg;
            exit;
            $result = [
                'message' => $this->_($msg),
                'status'  => $status
            ];
            $this->BResponse->json($result);
        }
    }
}
