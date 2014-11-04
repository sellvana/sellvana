<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_ShippingPlain_ShippingMethod
 */
class FCom_ShippingPlain_ShippingMethod extends FCom_Sales_Method_Shipping_Abstract
{
    /**
     * @var string
     */
    protected $_name = 'Plain Shipping';
    /**
     *
     */
    const FREE_SHIPPING = "free";

    /**
     * @return string
     */
    public function getEstimate()
    {
        return '2-4 days';
    }

    /**
     * @return array
     */
    public function getServices()
    {
        return ['01' => 'Air', '02' => 'Ground'];
    }

    /**
     * @return array
     */
    public function getDefaultService()
    {
        return ['02' => 'Ground'];
    }

    /**
     * @return array
     */
    public function getServicesSelected()
    {
        $c = $this->BConfig;
        $selected = [];
        foreach ($this->getServices() as $sId => $sName) {
            if ($c->get('modules/FCom_ShippingPlain/services/s' . $sId) == 1) {
                $selected[$sId] = $sName;
            }
        }
        if (empty($selected)) {
            $selected = $this->getDefaultService();
        }
        return $selected;
    }

    /**
     * @param $cart
     * @return int
     */
    public function getRateCallback($cart)
    {
        return 100;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Standard Shipping';
    }
}
