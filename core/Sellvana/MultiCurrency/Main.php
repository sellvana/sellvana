<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Main
 *
 */
class Sellvana_MultiCurrency_Main extends BClass
{
    static protected $_rateSources = [];

    static protected $_defaultRateProvider = 'Sellvana_MultiCurrency_RateProvider_OpenExchangeRates';

    static protected $_rates;

    public function bootstrap()
    {
        $this->addRateProvider(static::$_defaultRateProvider);
    }

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


    public function addRateProvider($class)
    {
        static::$_rateSources[$class] = $this->BClassRegistry->instance($class);
        return $this;
    }

    public function getAllRateProviders()
    {
        return static::$_rateSources;
    }

    public function getRateProviderOptions()
    {
        $options = [];
        /**
         * @var string $class
         * @var Sellvana_MultiCurrency_RateProvider_Interface $instance
         */
        foreach ($this->getAllRateProviders() as $class => $instance) {
            $options[$class] = $instance->getLabel();
        }
        return $options;
    }

    public function getActiveRateProvider()
    {
        $class = $this->BConfig->get('modules/Sellvana_MultiCurrency/active_rateprovider');
        if (!$class || empty(static::$_rateSources[$class])) {
            return $this->BClassRegistry->instance(static::$_defaultRateProvider);
        } else {
            return static::$_rateSources[$class];
        }
    }

}
