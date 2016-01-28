<?php

/**
 * Class Sellvana_ShippingPlain_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_ShippingPlain_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addShippingMethod('plain', 'Sellvana_ShippingPlain_ShippingMethod');
    }
}
