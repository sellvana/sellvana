<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiCurrency_RateProvider_Abstract
 *
 * @property Sellvana_MultiCurrency_Main $Sellvana_MultiCurrency_Main
 */
class Sellvana_MultiCurrency_RateProvider_Abstract extends BClass
{
    public function getLabel()
    {
        return $this->_label;
    }

    protected function _getAvailableCurrencies()
    {
        return $this->Sellvana_MultiCurrency_Main->getAvailableCurrencies();
    }

    protected function _saveRatesConfig($rates)
    {
        $this->BConfig->set('modules/Sellvana_MultiCurrency/exchange_rates', join("\n", $rates), false, true);
        $this->BConfig->writeConfigFiles('local');
    }
}