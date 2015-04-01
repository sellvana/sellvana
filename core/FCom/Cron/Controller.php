<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Cron_Controller
 *
 * @property FCom_Cron_Main $FCom_Cron_Main
 */

class FCom_Cron_Controller extends FCom_Core_Controller_Abstract
{
    public function action_run()
    {
        $r = $this->BRequest;
        if ($r->request('wait')) {
            $this->BConfig->set('modules/FCom_Cron/wait_secs', (int)$r->request('wait'));
        }
        if ($r->request('debug')) {
            $this->BDebug->level(BDebug::OUTPUT, $r->request('debug'));
        }
        $task = $r->param('task', true);
        $force = $r->request('force');
        $this->FCom_Cron_Main->run($task, $force);
        $this->BDebug->dumpLog();
        exit;
    }
}
