<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_ApiServer_V1_Order extends FCom_ApiServer_Controller_Abstract
{
    public function action_index()
    {
        $id = BRequest::i()->param('id');
        $len = BRequest::i()->get('len');
        if (!$len) {
            $len = 10;
        }
        $start = BRequest::i()->get('start');
        if (!$start) {
            $start = 0;
        }

        if ($id) {
            $orders[] = FCom_Sales_Model_Order::i()->load($id);
        } else {
            $orders = FCom_Sales_Model_Order::orm()->limit($len, $start)->find_many();
        }
        if (empty($orders)) {
            $this->ok();
        }
        $result = FCom_Sales_Model_Order::i()->prepareApiData($orders, true);
        $this->ok($result);
    }

    public function action_index__POST()
    {
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($post['customer_id'])) {
            $this->badRequest("Customer id is required");
        }
        if (empty($post['status']) || !in_array($post['status'], ['new', 'paid'])) {
            $this->badRequest("Status is required");
        }
        if (empty($post['item_qty'])) {
            $this->badRequest("Items quantity is required");
        }
        if (empty($post['items'])) {
            $this->badRequest("Items collection is required");
        }

        foreach ($post['items'] as $item) {
            if (empty($item['product_id'])) {
                $this->internalError("Can't create an order item. Product id is missing");
            }
            $product = FCom_Catalog_Model_Product::i()->load($item['product_id']);
            if (!$product) {
                $this->internalError("Can't create an order item. Product id #{$item['product_id']} not exist");
            }
        }

        $data = FCom_Sales_Model_Order::i()->formatApiPost($post);
        $order = false;
        try {
            $order = FCom_Sales_Model_Order::orm()->create($data)->save();
        } catch (Exception $e) {
            $this->internalError("Can't create an order");
        }
        if (!$order) {
            $this->internalError("Can't create an order");
        }

        foreach ($post['items'] as $item) {
            $product = FCom_Catalog_Model_Product::i()->load($item['product_id']);

            $orderItem = [];
            $orderItem['order_id'] = $order->id();
            $orderItem['product_id'] = $item['product_id'];
            $orderItem['qty'] = $item['qty'];
            $orderItem['total'] = $item['total'];
            $orderItem['product_info'] = BUtil::toJson($product->as_array());

            FCom_Sales_Model_Order_Item::i()->addNew($orderItem);
        }

        $this->created(['id' => $order->id]);
    }

    public function action_index__PUT()
    {
        $id = BRequest::i()->param('id');
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($id)) {
            $this->badRequest("Order id is required");
        }

        if (!empty($post['items'])) {
            foreach ($post['items'] as $item) {
                if (empty($item['product_id'])) {
                    $this->internalError("Can't create an order item. Product id is missing");
                }
                $product = FCom_Catalog_Model_Product::i()->load($item['product_id']);
                if (!$product) {
                    $this->internalError("Can't create an order item. Product id #{$item['product_id']} not exist");
                }
            }
        }

        $data = FCom_Sales_Model_Order::i()->formatApiPost($post);

        $order = FCom_Sales_Model_Order::i()->load($id);
        if (!$order) {
            $this->notFound("Order id #{$id} not found");
        }

        $order->set($data)->save();

        if (!empty($post['items'])) {
            foreach ($post['items'] as $item) {
                $product = FCom_Catalog_Model_Product::i()->load($item['product_id']);

                $orderItem = [];
                $orderItem['order_id'] = $order->id();
                $orderItem['product_id'] = $item['product_id'];
                $orderItem['qty'] = $item['qty'];
                $orderItem['total'] = $item['total'];
                $orderItem['product_info'] = BUtil::toJson($product->as_array());

                $testItem = FCom_Sales_Model_Order_Item::i()->isItemExist($order->id(), $item['product_id']);
                if ($testItem) {
                    $testItem->update($orderItem);
                } else {
                    FCom_Sales_Model_Order_Item::i()->addNew($orderItem);
                }
            }
        }

        $this->ok();
    }

    public function action_index__DELETE()
    {
        $id = BRequest::i()->param('id');

        if (empty($id)) {
            $this->notFound("Order id is required");
        }

        $order = FCom_Sales_Model_Order::i()->load($id);
        if (!$order) {
            $this->notFound("Order id #{$id} not found");
        }

        $order->delete();
        $this->ok();
    }


}
