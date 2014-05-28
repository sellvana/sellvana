<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Cron_Controller extends FCom_Core_Controller_Abstract
{
    public function action_run()
    {
        $r = BRequest::i();
        if ($r->request('wait')) {
            BConfig::i()->set('modules/FCom_Cron/wait_secs', (int)$r->request('wait'));
        }
        if ($r->request('debug')) {
            BDebug::level(BDebug::OUTPUT, $r->request('debug'));
        }
        $task = $r->param('task', true);
        $force = $r->request('force');
        FCom_Cron_Main::i()->run($task, $force);
        BDebug::dumpLog();
        exit;
    }
}