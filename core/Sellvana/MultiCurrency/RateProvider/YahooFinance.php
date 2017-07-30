<?php

class Sellvana_MultiCurrency_RateProvider_YahooFinance extends Sellvana_MultiCurrency_RateProvider_Abstract
    implements Sellvana_MultiCurrency_RateProvider_Interface
{
    static protected $_origClass = __CLASS__;

    protected $_label = (('Yahoo Finance'));

    protected $_apiUrl = 'http://quote.yahoo.com/d/quotes.csv';

    public function fetchRates()
    {
        $baseCur = $this->BConfig->get('modules/FCom_Core/base_currency', 'USD');
        $currencies = $this->_getAvailableCurrencies();
        $rates = [];
        foreach ($currencies as $cur) {
            if ($cur == $baseCur) {
                continue;
            }

            sleep(1);
            $url = $this->BUtil->setUrlQuery($this->_apiUrl, ['s' => $baseCur . $cur . '=x', 'f' => 'l1', 'e' => '.csv']);
            $response = $this->BUtil->remoteHttp('GET', $url);
            if (!$response) {
                throw new BException('Invalid Yahoo Finance response: ' . $response);
            }
            $rates[$cur] = $cur . ':' . trim($response);
        }

        $this->_saveRatesConfig($rates);
        return $this;
    }
}