<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */
class FCom_SampleData_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_load()
    {
        $xhr    = $this->BRequest->xhr();
        $msg    = "Sample products not imported.";
        $status = 'error';

        try {
            $this->BResponse->startLongResponse();
            $this->BConfig->set('db/logging', 0);

            $this->FCom_SampleData_Admin->loadProducts();
            $msg    = BLocale::_('Sample products imported');
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
                'message' => BLocale::_($msg),
                'status'  => $status
            ];
            $this->BResponse->json($result);
        }
    }
}
