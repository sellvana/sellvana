<?php

/**
 * Class Sellvana_ShippingEasyPost_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_ShippingFedex_Main extends BClass
{
    protected $_methodCode = 'fedex';

    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addShippingMethod($this->_methodCode, 'Sellvana_ShippingFedex_ShippingMethod');
    }

}