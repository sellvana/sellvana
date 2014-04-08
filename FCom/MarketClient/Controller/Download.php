<?php

class FCom_MarketClient_Controller_Download extends FCom_Core_Controller_Abstract
{
    public function action_index()
    {
        BLayout::i()->setRootView('marketclient/container');
        $this->view('marketclient/container')->set(array(
            'modules' => BRequest::i()->request('modules'),
            'redirect_to' => BRequest::i()->request('redirect_to'),
        ));
    }

    public function action_index__POST()
    {
        $this->action_index();
    }

    public function action_start__POST()
    {
        BResponse::i()->startLongResponse(false);
        ignore_user_abort();

        $modules = BRequest::i()->post('modules');
        $force = BRequest::i()->post('force');

        FCom_MarketClient_Main::i()->downloadAndInstall($modules, $force);
    }

    public function action_stop__POST()
    {
        FCom_MarketClient_Main::i()->stopDownloading();
    }

    public function action_progress()
    {
        $progress = FCom_MarketClient_Main::i()->progress();
        BResponse::i()->json(array(
            'progress' => $progress,
            'html' => (string)$this->view('marketclient/progress')->set('progress', $progress),
        ));
    }
}
