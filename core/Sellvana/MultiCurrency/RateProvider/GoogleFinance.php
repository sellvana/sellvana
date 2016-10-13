<?php

class Sellvana_MultiCurrency_RateProvider_GoogleFinance extends Sellvana_MultiCurrency_RateProvider_Abstract
    implements Sellvana_MultiCurrency_RateProvider_Interface
{
    static protected $_origClass = __CLASS__;

    protected $_label = 'Google Finance';

    protected $_apiUrl = 'http://www.google.com/finance/converter';

    public function fetchRates()
    {
        $baseCur = $this->BConfig->get('modules/FCom_Core/base_currency', 'USD');
        $currencies = $this->_getAvailableCurrencies();
        $rates = [];
        foreach ($currencies as $cur) {
            sleep(1);
            $url = $this->BUtil->setUrlQuery($this->_apiUrl, ['a' => 1, 'from' => $baseCur, 'to' => $cur]);
            $response = $this->BUtil->remoteHttp('GET', $url);
            if (!$response) {
                throw new BException('Invalid Google Finance response: ' . $response);
            }
            if (preg_match("'<span class=bld>([0-9\.]+)\s\w+</span>'", $response, $m)) {
                $rates[$cur] = $cur . ':' . $m[1];
            }
        }

        $this->_saveRatesConfig($rates);
        return $this;
    }
}