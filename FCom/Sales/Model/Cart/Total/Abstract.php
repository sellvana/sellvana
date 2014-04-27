<?php

abstract class FCom_Sales_Model_Cart_Total_Abstract extends BCLass implements FCom_Sales_Model_Cart_Total_Interface
{
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

    public function init( $cart )
    {
        $this->_cart = $cart;
        $this->_currency = $this->_cart->cart_currency;
        if ( !$this->_configPath ) {
            $this->_configPath = 'modules/FCom_Sales/cart_totals/' . $this->_code;
        }
        $this->_config = BConfig::i()->get( $this->_configPath );
        if ( !empty( $this->_config[ 'sort_order' ] ) ) {
            $this->_sortOrder = $this->_config[ 'sort_order' ];
        }
        if ( !empty( $cart->data[ 'totals' ][ $this->_code ] ) ) {
            $data = $cart->data[ 'totals' ][ $this->_code ];
            $this->_label = $data[ 'label' ];
            $this->_value = $data[ 'value' ];
        }
        return $this;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    public function getRowClass()
    {
        return $this->_rowClass ? $this->_rowClass : 'f-' . BUtil::simplifyString( $this->_label );
    }

    public function getLabel()
    {
        return BLocale::_( $this->_label );
    }

    public function getLabelFormatted()
    {
        return $this->getLabel();
    }

    public function getValue()
    {
        return $this->_cartField ? $this->_cart[ $this->_cartField ] : $this->_value;
    }

    public function getValueFormatted()
    {
        return BLocale::i()->currency( $this->getValue(), $this->_currency );
    }

    public function getCurrency()
    {
        return $this->_currency;
    }

    public function getError()
    {
        return false;
    }

    public function isHidden()
    {
        return !$this->_value;
    }

    public function calculate()
    {
        //PLACEHOLDER
        return $this;
    }

    public function asArray()
    {
        return array(
            'label' => $this->getLabel(),
            'value' => $this->getValue(),
            'currency' => $this->getCurrency(),
        );
    }
}
