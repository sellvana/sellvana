<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Main
 *
 */
class Sellvana_MultiCurrency_Main extends BClass
{
    static protected $_rates;

    public function switchCurrency($newCurrency)
    {
        $currencies = $this->getAvailableCurrencies();
        if (in_array($newCurrency, $currencies)) {
            $oldCurrency = $this->BSession->get('current_currency');
            $this->BSession->set('current_currency', $newCurrency);
            $this->BEvents->fire(__METHOD__, ['old_currency' => $oldCurrency, 'new_currency' => $newCurrency]);
        }
        return $this;
    }

    public function getAvailableCurrencies()
    {
        $curText = $this->BConfig->get('modules/Sellvana_MultiCurrency/available_currencies');
        if (!$curText) {
            return [];
        }
        $curArr = preg_split('/\s*,\s*/', $curText);
        return array_combine($curArr, $curArr);
    }

    public function getCurrentCurrency()
    {
        $def = $this->BConfig->get('modules/FCom_Core/default_currency');
        return $this->BSession->get('current_currency', $def);
    }

    public function fetchOpenExchangeRates()
    {
        $appId = $this->BConfig->get('modules/Sellvana_MultiCurrency/open_exchange_rates_app_id');
        if (!$appId) {
            throw new BException('Could not retrieve rates because App ID is not configured');
        }
        $baseCur = $this->BConfig->get('modules/FCom_Core/base_currency', 'USD');
        $url = $this->BUtil->setUrlQuery('https://openexchangerates.org/api/latest.json',
            ['app_id' => $appId, 'base' => $baseCur]);
        $response = $this->BUtil->remoteHttp('GET', $url);
        if (!$response) {
            throw new BException('Invalid OpenExchangeRates response: ' . $response);
        }
        $result = $this->BUtil->fromJson($response);
        if (empty($result['rates'])) {
            throw new BException('Invalid OpenExchangeRates response: ' . print_r($result, 1));
        }
        $currencies = $this->getAvailableCurrencies();
        $rates = [];
        foreach ($currencies as $cur) {
            if (!empty($result['rates'][$cur]) && $cur !== $baseCur) {
                $rates[$cur] = $cur . ':' . $result['rates'][$cur];
            }
        }
        $this->BConfig->set('modules/Sellvana_MultiCurrency/exchange_rates', join("\n", $rates), false, true);
        $this->BConfig->writeConfigFiles('local');
        return $this;
    }

    public function getAvailableRates()
    {
        if (null === static::$_rates) {
            $ratesConfig = $this->BConfig->get('modules/Sellvana_MultiCurrency/exchange_rates');
            if (!$ratesConfig) {
                static::$_rates = [];
                return null;
            }
            $baseCurrency = $this->BConfig->get('modules/FCom_Core/base_currency');
            $ratesArr = explode("\n", $ratesConfig);
            foreach ($ratesArr as $r) {
                list($cur, $rate) = explode(':', $r, 2) + [null];
                if ($cur && is_numeric($rate)) {
                    static::$_rates[$baseCurrency][$cur] = $rate;
                }
            }
        }
        return static::$_rates;
    }

    public function getRate($toCurrency = null, $fromCurrency = null)
    {
        static $rateCache = [];

        $baseCurrency = $this->BConfig->get('modules/FCom_Core/base_currency');
        if (null === $fromCurrency || true === $fromCurrency) {
            $fromCurrency = $baseCurrency;
        }
        if (null === $toCurrency || true === $toCurrency) {
            $toCurrency = $this->getCurrentCurrency();
        }
        $rates = $this->getAvailableRates();
        if (!empty($rates[$fromCurrency][$toCurrency])) {
            return $rates[$fromCurrency][$toCurrency];
        }
        if (empty($rates[$baseCurrency][$toCurrency])) {
            return null;
        }
        $rate = $rates[$baseCurrency][$toCurrency];
        if ($fromCurrency === $baseCurrency) {
            return $rate;
        }
        $baseRate = $this->getRate($fromCurrency);
        if (!$baseRate) {
            return null;
        }
        return $rate / $baseRate;
    }
}
