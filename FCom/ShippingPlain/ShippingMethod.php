<?php

class FCom_ShippingPlain_ShippingMethod extends FCom_Sales_Method_Shipping_Abstract
{
    protected $_name = 'Plain Shipping';
    const FREE_SHIPPING = "free";

    public function getEstimate()
    {
        return '2-4 days';
    }

    public function getServices()
    {
        return array( '01' => 'Air', '02' => 'Ground' );
    }

    public function getDefaultService()
    {
        return array( '02' => 'Ground' );
    }

    public function getServicesSelected()
    {
        $c = BConfig::i();
        $selected = array();
        foreach ( $this->getServices() as $sId => $sName ) {
            if ( $c->get( 'modules/FCom_ShippingPlain/services/s' . $sId ) == 1 ) {
                $selected[ $sId ] = $sName;
            }
        }
        if ( empty( $selected ) ) {
            $selected = $this->getDefaultService();
        }
        return $selected;
    }

    public function getRateCallback( $cart )
    {
        return 100;
    }

    public function getError()
    {
        return '';
    }

    public function getDescription()
    {
        return 'Standard Shipping';
    }
}
