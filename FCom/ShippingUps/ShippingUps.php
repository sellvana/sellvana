<?php

class FCom_ShippingUps extends FCom_Checkout_Model_Shipping_Abstract
{
    protected $rate;

    public static function bootstrap()
    {
        include_once __DIR__ .'/lib/UpsRate.php';
        FCom_Checkout_Model_Cart::i()->addShippingMethod('ShippingUps', 'FCom_ShippingUps');

    }

    public function apiCall($shipNumber, $fromzip, $tozip, $service, $length, $width, $height, $weight)
    {
        $this->rate = new UpsRate('FC7DECC55E57CF90','A31T84','UPS!@#*()', $shipNumber);
        $this->rate->getRate($fromzip, $tozip, $service, $length, $width, $height, $weight);
    }

    public function getEstimate($tozip=0, $service=01, $length=0, $width=0, $height=0, $weight=0)
    {
        if (!$this->rate) {
            $cart = FCom_Checkout_Model_Cart::sessionCart();
            $fromzip = 82108;
            $tozip = 90203;
            $service = $serviceNumber ? $serviceNumber : $cart->shipping_service;
            $length = $width = $height = $weight = 10;
            $this->apiCall($cart->id(), $fromzip, $tozip, $service, $length, $width, $height, $weight);
        }
        $estimate = $this->rate->getEstimate();
        if (!$estimate) {
            return 'Unable to calculate';
        }
        $days = ($estimate == 1) ? ' day' : ' days';
        return $estimate . $days;
    }

    /**
     * UPS services
     * @return array
     */
    public function getServices()
    {
        return array(
            '01' => 'UPS Next Day Air',
            '02' => 'UPS Second Day Air',
            '03' => 'UPS Ground',
            '07' => 'UPS Worldwide Express',
            '08' => 'UPS Worldwide Expedited',
            '11' => 'UPS Standard',
            '12' => 'UPS Three-Day Select',
            '13' => 'Next Day Air Saver',
            '14' => 'UPS Next Day Air Early AM',
            '54' => 'UPS Worldwide Express Plus',
            '59' => 'UPS Second Day Air AM',
            '65' => 'UPS Saver'
        );
    }

    public function getRateCallback($cart)
    {
        $this->rate = new UpsRate('FC7DECC55E57CF90','A31T84','UPS!@#*()', $cart->id());
        //address
        $fromzip = 82108;
        $shippingAddress = FCom_Checkout_Model_Address::i()->getAddress($cart->id(), 'shipping');
        $tozip = $shippingAddress->zip;
        //service
        $service = $cart->shipping_service;
        //package
        $length = $width = $height = 10;
        $weight = 10;
        return $this->apiCall($cart->id(), $fromzip, $tozip, $service, $length, $width, $height, $weight);
    }

    public function getError()
    {
        return $this->rate->getError();
    }

    public function getPrice()
    {
        return 2;
    }

    public function getDescription()
    {
        return 'Universal post service';
    }
}