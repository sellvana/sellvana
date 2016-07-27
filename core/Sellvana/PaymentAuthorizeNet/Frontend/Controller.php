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
    public function action_dpm__POST()
    {
        $result = $this->Sellvana_PaymentAuthorizeNet_PaymentMethod_Dpm->processReturnFromExternalCheckout();

        if (!empty($result['error'])) {
            $this->message($result['error']['message'], 'error');
            $this->BResponse->redirect('checkout');
            return;
        }

        $html = $this->BLayout->getView('authorizenet/dpm_relay')->set('redirect_url', $result['redirect_to'])->render();
        echo $html;
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

        // Send the Javascript back to AuthorizeNet, which will redirect user back to your site.
        //$response = $this->BResponse;
        $html = $this->BLayout->getView('authorizenet/dpm_relay')->set('redirect_url', $result['redirect_to'])->render();
        //$response->set($html);
        //$response->render();
        // somehow AuthorizeNet doesn't see output if it is made via BResponse
        echo $html;
        $this->BResponse->redirect('checkout/success');
    }

    public function isApiCall()
    {
        return true;
    }

}
