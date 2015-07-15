<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiCurrency_Admin_Controller
 *
 * @property Sellvana_MultiCurrency_Main $Sellvana_MultiCurrency_Main
 */
class Sellvana_MultiCurrency_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_fetch_exchange_rates__POST()
    {
        $xhr = $this->BRequest->xhr();
        $result = [];
        try {
            $this->Sellvana_MultiCurrency_Main->getActiveRateSource()->fetchRates();
            if ($xhr) {
                $result['success'] = true;
                $result['rates'] = $this->BConfig->get('modules/Sellvana_MultiCurrency/exchange_rates');
            } else {
                $this->message('Exchange Rates Fetched');
            }
        } catch (Exception $e) {
            if ($xhr) {
                $result['error']['message'] = $e->getMessage();
            } else {
                $this->message($e->getMessage(), 'error');
            }
        }
        if ($xhr) {
            $this->BResponse->json($result);
        } else {
            $this->BResponse->redirect($this->BRequest->referrer());
        }
    }
}