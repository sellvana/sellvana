<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Main
 *
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 *
 * @method FCom_Sales_Main i() static i($new=false, array $args=array())
 */
class FCom_Sales_Main extends BClass
{
    protected $_registry = [];
    protected $_heap = [];

    public function bootstrap()
    {
        foreach (['Subtotal', 'Shipping', 'Discount', 'GrandTotal'] as $total) {
            $this->FCom_Sales_Model_Cart->registerTotalRowHandler('FCom_Sales_Model_Cart_Total_' . $total);
        }

        $this->FCom_Admin_Model_Role->createPermission([
            'sales' => 'Sales',
            'sales/orders' => 'Orders',
            'sales/order_status' => 'Order Status',
            'sales/carts' => 'Carts',
            'sales/reports' => 'Reports'
        ]);

        foreach (['Cart', 'Checkout', 'Order', 'OrderItem', 'Payment', 'Shipment', 'Cancel', 'Return', 'Refund', 'Comment'] as $workflow) {
            $this->{'FCom_Sales_Workflow_' . $workflow}->registerWorkflow();
        }
    }

    /**
     * @param $name
     * @param null $class
     * @return $this
     */
    public function addPaymentMethod($name, $class = null)
    {
        if (is_null($class)) {
            $class = $name;
        }
        $this->_registry['payment_method'][$name] = $class;
        return $this;
    }

    /**
     * @param $name
     * @param null $class
     * @return $this
     */
    public function addCheckoutMethod($name, $class = null)
    {
        if (is_null($class)) {
            $class = $name;
        }
        $this->_registry['checkout_method'][$name] = $class;
        return $this;
    }

    /**
     * @param $name
     * @param null $class
     * @return $this
     */
    public function addShippingMethod($name, $class = null)
    {
        if (is_null($class)) {
            $class = $name;
        }
        $this->_registry['shipping_method'][$name] = $class;
        return $this;
    }

    /**
     * @param $name
     * @param null $class
     * @return $this
     */
    public function addDiscountMethod($name, $class = null)
    {
        if (is_null($class)) {
            $class = $name;
        }
        $this->_registry['discount_method'][$name] = $class;
        return $this;
    }

    /**
     * @param $name
     * @return null
     */
    public function getShippingMethodClassName($name)
    {
        return !empty($this->_registry['shipping_method'][$name]) ? $this->_registry['shipping_method'][$name] : null;
    }

    /**
     * @param $type
     * @param null $name
     * @return null
     */
    protected function _getHeap($type, $name = null)
    {
        if (empty($this->_heap[$type])) {
            $this->_heap[$type] = null; // make sure key exists
            if (!empty($this->_registry[$type])) {
                foreach ($this->_registry[$type] as $n => $class) {
                    $this->_heap[$type][$n] = $this->{$class};
                }
                uasort($this->_heap[$type], function ($a, $b) {
                    return $a->getSortOrder() - $b->getSortOrder();
                });
            }
        }
        return is_null($name) ? $this->_heap[$type] :
            (!empty($this->_heap[$type][$name]) ? $this->_heap[$type][$name] : null);
    }

    /**
     * @return null
     */
    public function getPaymentMethods()
    {
        return $this->_getHeap('payment_method');
    }

    /**
     * @return null
     */
    public function getCheckoutMethods()
    {
        return $this->_getHeap('checkout_method');
    }

    /**
     * @return null
     */
    public function getShippingMethods()
    {
        return $this->_getHeap('shipping_method');
    }

    /**
     * @return null
     */
    public function getDiscountMethods()
    {
        return $this->_getHeap('discount_method');
    }

    public function getAllSelectedShippingServices()
    {
        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        $estimates = $cart->getData('shipping_estimates');

        $services = [];
        foreach ($this->getShippingMethods() as $mKey => $method) {
            if (!$method->getConfig('enabled')) {
                continue;
            }
            foreach ($method->getServicesSelected() as $sKey => $sLabel) {
                $services[$mKey]['services'][$sKey]['value'] = $mKey . ':' . $sKey;
                $services[$mKey]['services'][$sKey]['label'] = $sLabel;
                if ($estimates && !empty($estimates[$mKey][$sKey])) {
                    $services[$mKey]['services'][$sKey]['estimate'] = $estimates[$mKey][$sKey];
                }
                //var_dump($mKey, $sKey, $cart->get('shipping_method'), $cart->get('shipping_service'), '<hr>');
                if ($cart && $cart->get('shipping_method') == $mKey && $cart->get('shipping_service') == $sKey) {
                    $services[$mKey]['services'][$sKey]['selected'] = true;
                }
            }
            if (!empty($services[$mKey]['services'])) {
                $services[$mKey]['name'] = $method->getName();
                $services[$mKey]['description'] = $method->getDescription();
            }
        }
        return $services;
    }


    /**
     * @param $args
     */
    public function checkDefaultShippingPayment($args)
    {
        if (!$this->getShippingMethods()) {
            $args['notifications'][] = [
                'type' => 'warning',
                'group' => 'FCom Sales',
                'message' => 'You have to enable at least one shipping module',
                'code' => "sales_missing_shipping",
            ];
        }
        if (!$this->getPaymentMethods()) {
            $args['notifications'][] = [
                'type' => 'warning',
                'group' => 'FCom Sales',
                'message' => 'You have to enable at least one payment module',
                'code' => "sales_missing_payment",
            ];
        }
    }

    /**
     * @param $args
     */
    public function onGetDashboardWidgets($args)
    {
        $view = $args['view'];
        $view->addWidget('orders-list', [
            'title' => 'Recent Orders',
            'icon' => 'inbox',
            'view' => 'order/dashboard/orders-list',
            'async' => true,
        ]);
        $view->addWidget('orders-totals', [
            'title' => 'Order Totals',
            'icon' => 'inbox',
            'view' => 'order/dashboard/orders-totals',
            'cols' => 4,
            'async' => true,
            'filter' => true
        ]);
    }

    public function workflowAction($actionName, $args = [])
    {
        return $this->BEvents->fire('FCom_Sales_Workflow::' . $actionName, $args);
    }

    public function onCustomerLogIn($args)
    {
        $this->workflowAction('customerLogsIn', $args);
    }

    public function onCustomerLogOut($args)
    {
        $this->workflowAction('customerLogsOut', $args);
    }
}

