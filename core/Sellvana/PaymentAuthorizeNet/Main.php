<?php

/**
 * Created by pp
 * @project fulleron
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */

class Sellvana_PaymentAuthorizeNet_Main extends BClass
{
    public function bootstrap()
    {
        $this->BClassAutoload->addPath(__DIR__ . '/lib');

        if ($this->BConfig->get('modules/Sellvana_PaymentAuthorizeNet/aim/active')) {
            $this->Sellvana_Sales_Main->addPaymentMethod('authnetaim', 'Sellvana_PaymentAuthorizeNet_PaymentMethod_Aim');
        }
        if ($this->BConfig->get('modules/Sellvana_PaymentAuthorizeNet/dpm/active')) {
            $this->Sellvana_Sales_Main->addPaymentMethod('authnetdpm', 'Sellvana_PaymentAuthorizeNet_PaymentMethod_Dpm');
        }
        if ($this->BConfig->get('modules/Sellvana_PaymentAuthorizeNet/sim/active')) {
            $this->Sellvana_Sales_Main->addPaymentMethod('authnetsim', 'Sellvana_PaymentAuthorizeNet_PaymentMethod_Sim');
        }
    }
}
