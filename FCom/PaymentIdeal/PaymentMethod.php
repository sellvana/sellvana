<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_PaymentIdeal_PaymentMethod
    extends FCom_Sales_Method_Payment_Abstract
{
    /**
     * @var Mollie_iDEAL_Payment
     */
    protected $api;

    /**
     * @var BData
     */
    protected $config;

    protected $_name = "iDEAL";

    public function payOnCheckout()
    {
        $api = $this->getApi();

        $bankId = $this->details['bank_id'];
        $amount = $this->details['amount_due'] * 100;
        $description = $this->salesEntity->getTextDescription();
        $returnUrl = BApp::href("checkout/success");
        $reportUrl = BApp::href("ideal/report");

        if ($api->createPayment($bankId, $amount, $description, $returnUrl, $reportUrl)) {
            $this->details['transaction_id'] = $api->getTransactionId();
            $this->details['bank_url'] = $api->getBankURL();
        } else {
            $this->details['error'] = $api->getErrorMessage();
            $this->details['error_code'] = $api->getErrorCode();
        }

        $success = !isset($this->details['error']);
        if ($success) {
            $status = 'processing';
        } else {
            $status = 'error';
        }
        $paymentData = array(
            'method'           => 'ideal',
            'parent_id'        => $api->getTransactionId(),
            'order_id'         => $this->salesEntity->id(),
            'amount'           => $amount,
            'status'           => $status,
            'transaction_id'   => $api->getTransactionId(),
            'transaction_type' => 'sale',
            'online'           => 1,
        );

        $paymentModel = FCom_Sales_Model_Order_Payment::i()->addNew($paymentData);
        $paymentModel->setData('response', $this->getPublicData());
        $paymentModel->save();
    }

    public function getCheckoutFormView()
    {
        $api = $this->getApi();
        $banks = $api->getBanks();
        return BLayout::i()->view('form')
               ->set('banks', $banks)
               ->set('key', 'ideal');
    }

    /**
     * Get method config
     *
     * Returns method config wrapped in BData object for more convenient access
     * @return BData
     */
    public function config()
    {
        if(!$this->config){
            $this->config = BData::i(true, BConfig::i()->get('modules/FCom_PaymentIdeal'));
        }
        return $this->config;
    }

    /**
     * Get API object
     * @return Mollie_iDEAL_Payment
     */
    public function getApi()
    {
        if(!$this->api){
            $partner_id = $this->config()->get('partner_id');
            $testMode = $this->config()->get('test');
            $profile = $this->config()->get('profile_key');
            $this->api = new Mollie_iDEAL_Payment($partner_id);
            $this->api->setTestmode((bool) $testMode);
            if($profile){
                $this->api->setProfileKey($profile);
            }
        }
        return $this->api;
    }
}