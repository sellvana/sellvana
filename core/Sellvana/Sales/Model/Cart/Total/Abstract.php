<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Model_Cart_Total_Abstract
 */
abstract class Sellvana_Sales_Model_Cart_Total_Abstract extends BClass implements Sellvana_Sales_Model_Cart_Total_Interface
{
    /** @var Sellvana_Sales_Model_Cart $_cart */
    protected $_cart;
    protected $_code;
    protected $_configPath;
    protected $_config;
    protected $_sortOrder = 80;
    protected $_label;
    protected $_rowClass;
    protected $_cartField;
    protected $_value = 0;
    protected $_currency;
    protected $_storeCurrencyValue = 0;

    /**
     * @param Sellvana_Sales_Model_Cart $cart
     * @return $this
     */
    public function init($cart)
    {
        $this->_cart = $cart;
        $this->_currency = $this->_cart->get('store_currency_code');
        if (!$this->_configPath) {
            $this->_configPath = 'modules/Sellvana_Sales/cart_totals/' . $this->_code;
        }
        $this->_config = $this->BConfig->get($this->_configPath);
        if (!empty($this->_config['sort_order'])) {
            $this->_sortOrder = $this->_config['sort_order'];
        }
        if (!empty($cart->data['totals'][$this->_code])) {
            $data = $cart->data['totals'][$this->_code];
            $this->_label = $data['label'];
            $this->_value = $data['value'];
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /**
     * @return string
     * @throws BException
     */
    public function getRowClass()
    {
        return $this->_rowClass ? $this->_rowClass : 'f-' . $this->BUtil->simplifyString($this->_label);
    }

    /**
     * @return false|string
     */
    public function getLabel()
    {
        return $this->BLocale->_($this->_label);
    }

    /**
     * @return false|string
     */
    public function getLabelFormatted()
    {
        return $this->getLabel();
    }

    /**
     * @param boolean $calculated return only the calculated value
     * @return int
     */
    public function getValue($calculated = false)
    {
        if ($calculated) {
            return $this->_value;
        }
        return $this->_cartField ? $this->_cart->get($this->_cartField) : $this->_value;
    }

    public function getStoreCurrencyValue($calculated = false)
    {
        if ($calculated) {
            return $this->_storeCurrencyValue;
        }
        return $this->_cartField ? $this->_cart->getData('store_currency/' . $this->_cartField) : $this->_storeCurrencyValue;
    }

    /**
     * @return string
     */
    public function getValueFormatted()
    {
        return $this->BLocale->currency($this->getStoreCurrencyValue());
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->_currency;
    }

    /**
     * @return bool
     */
    public function getError()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        $value = $this->getValue();
        $value = is_numeric($value) ? (float)$value : $value;
        return !$value;
    }

    /**
     * @return $this
     */
    public function calculate()
    {
        //PLACEHOLDER
        return $this;
    }

    /**
     * @return array
     */
    public function asArray()
    {
        return [
            'label' => $this->getLabel(),
            'value' => $this->getValue(),
            'currency' => $this->getCurrency(),
        ];
    }
}
