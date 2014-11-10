<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_MarketClient_Controller_Download
 *
 * @property FCom_MarketClient_Main $FCom_MarketClient_Main
 */
class FCom_MarketClient_Controller_Download extends FCom_Core_Controller_Abstract
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
        $this->FCom_MarketClient_Main->progress([], true);
    }

    public function action_start__POST()
    {
        $this->BResponse->startLongResponse(false);
        ignore_user_abort();

        $modules = $this->BRequest->post('modules');
        $force = $this->BRequest->post('force');

        $this->FCom_MarketClient_Main->downloadAndInstall($modules, $force);
    }

    public function action_stop__POST()
    {
        $this->FCom_MarketClient_Main->stopDownloading();
    }

    public function action_progress()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->status(403);
        }

        $progress = $this->FCom_MarketClient_Main->progress();
        $this->BResponse->json([
            'progress' => $progress,
            'html' => (string)$this->view('marketclient/progress')->set('progress', $progress),
        ]);
    }
}
