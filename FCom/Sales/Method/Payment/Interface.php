<?php defined('BUCKYBALL_ROOT_DIR') || die();

interface FCom_Sales_Method_Payment_Interface
{
    /**
     * @param FCom_Sales_Model_Order_Payment $payment
     * @return mixed
     */
    public function payOnCheckout();

    /**
     * Set any details gathered during checkout process
     * @param array $details
     * @return $this
     */
    public function setDetails($details);

    /**
     * Get public data
     *
     * Get data which can be saved, should not include any sensitive data such as credit card numbers, personal ids, etc.
     * @return array
     */
    public function getPublicData();
}
