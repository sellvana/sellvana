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
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
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
            $this->BResponse->redirect('orders');
        } catch (Exception $e) {

        }
    }

    public function action_merge_order__POST()
    {
        try {
            $post = $this->BRequest->post('merge');
            if (!empty($post['id'])) {

            }
            $orderId = $this->BSession->get('last_order_id');
            if (!$orderId) {
                $this->BResponse->redirect('');
                return;
            }
            $result = [];
            $this->Sellvana_Sales_Main->workflowAction('customerMergesOrderToAccount', [
                'order_id' => $orderId,
                'post' => $this->BRequest->post(),
                'result' => &$result,
            ]);
            $this->BResponse->redirect('orders');

        } catch (Exception $e) {

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