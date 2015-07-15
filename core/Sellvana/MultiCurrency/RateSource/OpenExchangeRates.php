<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_MultiCurrency_RateSource_OpenExchangeRates extends Sellvana_MultiCurrency_RateSource_Abstract
    implements Sellvana_MultiCurrency_RateSource_Interface
{
    static protected $_origClass = __CLASS__;

    protected $_label = 'Open Exchange Rates';

    protected $_apiUrl = 'https://openexchangerates.org/api/latest.json';

    public function fetchRates()
    {
        $appId = $this->BConfig->get('modules/Sellvana_MultiCurrency/open_exchange_rates_app_id');
        if (!$appId) {
            throw new BException('Could not retrieve rates because App ID is not configured');
        }
        $baseCur = $this->BConfig->get('modules/FCom_Core/base_currency', 'USD');
        $url = $this->BUtil->setUrlQuery($this->_apiUrl, ['app_id' => $appId, 'base' => $baseCur]);
        $response = $this->BUtil->remoteHttp('GET', $url);
        if (!$response) {
            throw new BException('Invalid OpenExchangeRates response: ' . $response);
        }
        $result = $this->BUtil->fromJson($response);
        if (empty($result['rates'])) {
            throw new BException('Invalid OpenExchangeRates response: ' . print_r($result, 1));
        }
        $currencies = $this->_getAvailableCurrencies();
        $rates = [];
        foreach ($currencies as $cur) {
            if (!empty($result['rates'][$cur]) && $cur !== $baseCur) {
                $rates[$cur] = $cur . ':' . $result['rates'][$cur];
            }
        }
        $this->_saveRatesConfig($rates);
        return $this;
    }
}