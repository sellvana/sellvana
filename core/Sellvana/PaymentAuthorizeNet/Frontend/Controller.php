<?php

/**
 * Created by pp
 * Class Sellvana_PaymentAuthorizeNet_Frontend_Controller
 * @project fulleron
 *
 * @property Sellvana_PaymentAuthorizeNet_PaymentMethod_Dpm $Sellvana_PaymentAuthorizeNet_PaymentMethod_Dpm
 * @property Sellvana_PaymentAuthorizeNet_PaymentMethod_Sim $Sellvana_PaymentAuthorizeNet_PaymentMethod_Sim
 */

class Sellvana_PaymentAuthorizeNet_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_dpm()
    {
        $result = $this->Sellvana_PaymentAuthorizeNet_PaymentMethod_Dpm->processReturnFromExternalCheckout();

        if (!empty($result['error'])) {
            $this->message($result['error']['message'], 'error');
            $this->BResponse->redirect('checkout');
            return;
        }

        $this->BResponse->redirect('checkout/success');
    }

    public function action_sim__POST()
    {
        //$this->BDebug->log(print_r($this->BRequest->post(), 1), 'sim.log');
        $result = $this->Sellvana_PaymentAuthorizeNet_PaymentMethod_Sim->processReturnFromExternalCheckout();
        if (!empty($result['error'])) {
            $this->message($result['error']['message'], 'error');
            $this->BResponse->redirect('checkout');
            return;
        }

        $this->BResponse->redirect('checkout/success');
    }

    public function isApiCall()
    {
        return true;
    }

}
