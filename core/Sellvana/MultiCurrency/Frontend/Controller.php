<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiCurrency_Frontend_Controller
 *
 * @property Sellvana_MultiCurrency_Main $Sellvana_MultiCurrency_Main
 */
class Sellvana_MultiCurrency_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_switch()
    {
        $ref = $this->BRequest->referrer();
        $cur = $this->BRequest->param('currency', true);
        $currencies = $this->Sellvana_MultiCurrency_Main->getAvailableCurrencies();
        if (in_array($cur, $currencies)) {
            $this->BSession->set('current_currency', $cur);
        }
        $this->BResponse->redirect($ref);
    }
}