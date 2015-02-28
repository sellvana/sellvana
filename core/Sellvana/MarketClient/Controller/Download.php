<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MarketClient_Controller_Download
 *
 * @property Sellvana_MarketClient_Main $Sellvana_MarketClient_Main
 */
class Sellvana_MarketClient_Controller_Download extends FCom_Core_Controller_Abstract
{
    public function action_index__POST()
    {
        #echo 1; exit;
        $r = $this->BRequest;
        $this->BLayout->setRootView('marketclient/container');
        $redirect = $r->request('redirect_to');
        if (!$r->isUrlLocal($redirect)) {
            $redirect = '';
        }

        $this->view('marketclient/container')->set([
            'modules' => $r->request('modules'),
            'redirect_to' => $redirect,
        ]);
        $this->Sellvana_MarketClient_Main->progress([], true);
    }

    public function action_start__POST()
    {
        $this->BResponse->startLongResponse(false);
        ignore_user_abort();

        $modules = $this->BRequest->post('modules');
        $force = $this->BRequest->post('force');

        try {
            $result = $this->Sellvana_MarketClient_Main->downloadAndInstall($modules, $force);
        } catch (Exception $e) {
            $result = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
        $this->BResponse->json($result);
    }

    public function action_stop__POST()
    {
        $this->Sellvana_MarketClient_Main->stopDownloading();
    }

    public function action_progress()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->status(403);
        }

        $progress = $this->Sellvana_MarketClient_Main->progress();
        $this->BResponse->json([
            'progress' => $progress,
            'html' => (string)$this->view('marketclient/progress')->set('progress', $progress),
        ]);
    }
}
