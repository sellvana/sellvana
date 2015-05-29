<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Frontend_Controller_Guest
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_Sales_Frontend_Controller_Guest extends FCom_Frontend_Controller_Abstract
{
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function authenticate($args = [])
    {
        if ($this->Sellvana_Customer_Model_Customer->isLoggedIn()) {
            return true;
        }
        if ($this->_action === 'add_to_account') {
            return false;
        }
        return true;
    }

    public function getGuestOrder()
    {
        $lastOrderId = $this->BSession->get('last_order_id');
        if ($lastOrderId) {
            $order = $this->Sellvana_Sales_Model_Order->load($lastOrderId);
            if (!$order) {
                return false;
            }
        } else {
            $reqOrder = $this->BRequest->get('id');
            $reqToken = $this->BRequest->get('token');
            if (!$reqOrder || !$reqToken) {
                return false;
            }
            $order = $this->Sellvana_Sales_Model_Order->load($reqOrder, 'unique_id');
            if (!$order || $order->get('token') !== $reqToken) {
                return false;
            }
        }
        return $order;
    }

    public function action_add_to_account()
    {
        $order = $this->getGuestOrder();
        if (!$order) {
            $this->forward(false);
            return;
        }

        $result = [];
        $this->Sellvana_Sales_Main->workflowAction('customerMergesOrderToAccount', [
            'order' => $order,
            'result' => &$result,
        ]);

        if (empty($result['error'])) {
            $this->message('Order has been merged to your account');
            $this->BResponse->redirect('orders/view?id=' . $order->get('unique_id'));
        } else {
            $this->message($result['error']['message'], 'error');
            $this->BResponse->redirect('orders');
        }

    }

    public function action_create_account()
    {
        $order = $this->getGuestOrder();
        if (!$order) {
            $this->forward(false);
            return;
        }

        $this->view('guest/create-account')->set('order', $order);

        $this->layout('/guest/create_account');
    }

    public function action_create_account__POST()
    {
        try {
            $orderId = $this->BSession->get('last_order_id');
            if (!$orderId) {
                $this->BResponse->redirect('');
                return;
            }
            $result = [];
            $this->Sellvana_Sales_Main->workflowAction('customerCreatesAccountFromOrder', [
                'order_id' => $orderId,
                'post' => $this->BRequest->post(),
                'result' => &$result,
            ]);

            $this->message('Account successfully created');
            $this->BResponse->redirect('orders');
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
            $this->BResponse->redirect($this->BRequest->referrer());
        }
    }

    public function action_find_order()
    {
        $this->layout('/guest/find_order');
    }

    public function action_find_order__POST()
    {
        $post = $this->BRequest->post('find');
        if (!$this->BValidate->validateInput($post, [
            ['id', '@required', 'Order ID is required'],
            ['email', '@required', 'Email is required'],
            ['email', '@email', 'Email is invalid'],
            ['postcode', '@required', 'Shipping Zip Code is required'],
        ], 'find_order')) {
            $this->BResponse->redirect('guest/find_order');
            return;
        }

        $order = false;
        $ok = $this->BLoginThrottle->init('Sellvana_Sales_Model_Order', $post['id']);
        if ($ok) {
            $order = $this->Sellvana_Sales_Model_Order->loadWhere([
                'unique_id' => $post['id'],
                'customer_email' => $post['email'],
                'shipping_postcode' => $post['postcode'],
            ]);
            if (!$order) {
                $this->BLoginThrottle->failure();
            }
        }
        if (!$order) {
            $this->message('Order not found', 'error');
            $this->BResponse->redirect('guest/find_order');
            return;
        }
        $this->BLoginThrottle->success();

        $orderIds = (array)$this->BSession->get('allowed_orders');
        $orderIds[$post['id']] = $order->id();
        $this->BSession->set('allowed_orders', $orderIds);

        $this->BResponse->redirect('orders/view?id=' . $post['id']);
    }
}