<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Cron
 *
 * @property Sellvana_MultiCurrency_Main $Sellvana_MultiCurrency_Main
 */
class Sellvana_MultiCurrency_Cron extends BClass
{
    public function runDaily($args)
    {
        if ($this->BConfig->get('modules/Sellvana_MultiCurrency/autofetch')) {
            $this->Sellvana_MultiCurrency_Main->getActiveRateSource()->fetchRates();
        }
    }
}
