<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Main
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 *
 * @method Sellvana_Sales_Main i() static i($new=false, array $args=array())
 */
class Sellvana_Sales_Main extends BClass
{
    protected $_registry = [];
    protected $_heap = [];

    public function bootstrap()
    {
        $locale = BLocale::i();
        $this->FCom_Admin_Model_Role->createPermission([
            'sales' => $locale->_('Sales'),
            'sales/orders' => $locale->_('Orders'),
            'sales/order_status' => $locale->_('Order Status'),
            'sales/order_custom_state' => $locale->_('Order Custom State'),
            'sales/carts' => $locale->_('Carts'),
            'sales/reports' => $locale->_('Reports'),
            'settings/Sellvana_Sales' => $locale->_('Sales Settings'),
            'settings/Sellvana_SalesShipping' => $locale->_('Sales Shipping Settings'),
            'settings/Sellvana_SalesPaymentMethods' => $locale->_('Sales Payment Methods Settings'),
        ]);

        foreach (['Subtotal', 'Shipping', 'Tax', 'Discount', 'GrandTotal', 'AmountDue'] as $total) {
            $this->Sellvana_Sales_Model_Cart->registerTotalRowHandler('Sellvana_Sales_Model_Cart_Total_' . $total);
        }

        foreach (['Cart', 'Checkout', 'Order', 'OrderItem', 'Payment', 'Shipment', 'Cancel', 'Return', 'Refund',
                     'Comment'] as $workflow) {
            $this->addWorkflow('Sellvana_Sales_Workflow_' . $workflow);
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

    public function addWorkflow($name, $class = null)
    {
        if (is_null($class)) {
            $class = $name;
        }
        $this->_registry['workflow'][$name] = $class;
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
     * @return Sellvana_Sales_Method_Payment_Abstract[]
     */
    public function getPaymentMethods()
    {
        return $this->_getHeap('payment_method');
    }

    /**
     * @return Sellvana_Sales_Method_Checkout_Abstract[]
     */
    public function getCheckoutMethods()
    {
        return $this->_getHeap('checkout_method');
    }

    /**
     * @return Sellvana_Sales_Method_Shipping_Abstract[]
     */
    public function getShippingMethods()
    {
        return $this->_getHeap('shipping_method');
    }

    /**
     * @return Sellvana_Sales_Method_Discount_Interface[]
     */
    public function getDiscountMethods()
    {
        return $this->_getHeap('discount_method');
    }

    /**
     * @return array
     */
    public function getAllSelectedShippingServices()
    {
        $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();
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
     * @param array $args
     */
    public function checkDefaultShippingPayment($args)
    {
        if (!$this->getShippingMethods()) {
            $args['items'][] = [
                'feed' => 'local',
                'type' => 'warning',
                'group' => 'FCom Sales',
                'content' => 'You have to enable at least one SHIPPING module',
                'code' => "sales_missing_shipping",
            ];
        }
        if (!$this->getPaymentMethods()) {
            $args['items'][] = [
                'feed' => 'local',
                'type' => 'warning',
                'group' => 'FCom Sales',
                'content' => 'You have to enable at least one PAYMENT module',
                'code' => "sales_missing_payment",
            ];
        }
    }

    /**
     * @param array $args
     */
    public function onGetDashboardWidgets($args)
    {
        $view = $args['view'];
        $view->addWidget('orders-list', [
            'title' => 'Recent Orders',
            'icon' => 'inbox',
            'view' => 'order/dashboard/orders-list',
            'async' => true,
            'filter' => true,
        ]);
        $view->addWidget('orders-totals', [
            'title' => 'Order Totals',
            'icon' => 'inbox',
            'view' => 'order/dashboard/orders-totals',
            'cols' => 4,
            'async' => true,
            'filter' => true
        ]);
        $view->addWidget('avg-order-value', [
            'title' => 'Average Order Value',
            'icon' => 'inbox',
            'cols' => 2,
            'view' => 'order/dashboard/avg-order-value',
            'async' => true,
            'filter' => true
        ]);
        $view->addWidget('top-products', [
            'title' => 'Top Products',
            'icon' => 'inbox',
            'cols' => 4,
            'view' => 'order/dashboard/top-products',
            'async' => true,
            'filter' => true
        ]);
    }

    public function workflowAction($actionName, $args = [])
    {
        #return $this->BEvents->fire('Sellvana_Sales_Workflow::' . $actionName, $args);

        $method = 'action_' . $actionName;
        $result = [];
        foreach ($this->_registry['workflow'] as $workflow => $class) {
            if (is_string($class)) {
                $class = $this->BClassRegistry->instance($class);
                $this->_registry['workflow'][$workflow] = $class;
            }
            if (method_exists($class, $method)) {
                try {
                    $result[] = $class->$method($args);
                } catch (Sellvana_Sales_Workflow_Exception_Recoverable $e) {
                    $result['errors'][] = [
                        'workflow' => $workflow,
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }
        return $result;
    }

    public function onCustomerLogIn($args)
    {
        $this->workflowAction('customerLogsIn', $args);
    }

    public function onCustomerLogOut($args)
    {
        $this->workflowAction('customerLogsOut', $args);
    }

    public function onSwitchCurrency($args)
    {
        $cart = $this->Sellvana_Sales_Model_Cart->sessionCart(true);
        $cart->setStoreCurrency($args['new_currency'])->calculateTotals()->saveAllDetails();
    }
}
