<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_ApiServer_V1_Order extends FCom_ApiServer_Controller_Abstract
{
    public function action_index()
    {
        $id = $this->BRequest->param('id');
        $len = $this->BRequest->get('len');
        if (!$len) {
            $len = 10;
        }
        $start = $this->BRequest->get('start');
        if (!$start) {
            $start = 0;
        }

        if ($id) {
            $orders[] = $this->FCom_Sales_Model_Order->load($id);
        } else {
            $orders = $this->FCom_Sales_Model_Order->orm()->limit($len, $start)->find_many();
        }
        if (empty($orders)) {
            $this->ok();
        }
        $result = $this->FCom_Sales_Model_Order->prepareApiData($orders, true);
        $this->ok($result);
    }

    public function action_index__POST()
    {
        $post = $this->BUtil->fromJson($this->BRequest->rawPost());

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
            $product = $this->FCom_Catalog_Model_Product->load($item['product_id']);
            if (!$product) {
                $this->internalError("Can't create an order item. Product id #{$item['product_id']} not exist");
            }
        }

        $data = $this->FCom_Sales_Model_Order->formatApiPost($post);
        $order = false;
        try {
            $order = $this->FCom_Sales_Model_Order->create($data)->save();
        } catch (Exception $e) {
            $this->internalError("Can't create an order");
        }
        if (!$order) {
            $this->internalError("Can't create an order");
        }

        foreach ($post['items'] as $item) {
            $product = $this->FCom_Catalog_Model_Product->load($item['product_id']);

            $orderItem = [];
            $orderItem['order_id'] = $order->id();
            $orderItem['product_id'] = $item['product_id'];
            $orderItem['qty'] = $item['qty'];
            $orderItem['total'] = $item['total'];
            $orderItem['product_info'] = $this->BUtil->toJson($product->as_array());

            $this->FCom_Sales_Model_Order_Item->addNew($orderItem);
        }

        $this->created(['id' => $order->id]);
    }

    public function action_index__PUT()
    {
        $id = $this->BRequest->param('id');
        $post = $this->BUtil->fromJson($this->BRequest->rawPost());

        if (empty($id)) {
            $this->badRequest("Order id is required");
        }

        if (!empty($post['items'])) {
            foreach ($post['items'] as $item) {
                if (empty($item['product_id'])) {
                    $this->internalError("Can't create an order item. Product id is missing");
                }
                $product = $this->FCom_Catalog_Model_Product->load($item['product_id']);
                if (!$product) {
                    $this->internalError("Can't create an order item. Product id #{$item['product_id']} not exist");
                }
            }
        }

        $data = $this->FCom_Sales_Model_Order->formatApiPost($post);

        $order = $this->FCom_Sales_Model_Order->load($id);
        if (!$order) {
            $this->notFound("Order id #{$id} not found");
        }

        $order->set($data)->save();

        if (!empty($post['items'])) {
            foreach ($post['items'] as $item) {
                $product = $this->FCom_Catalog_Model_Product->load($item['product_id']);

                $orderItem = [];
                $orderItem['order_id'] = $order->id();
                $orderItem['product_id'] = $item['product_id'];
                $orderItem['qty'] = $item['qty'];
                $orderItem['total'] = $item['total'];
                $orderItem['product_info'] = $this->BUtil->toJson($product->as_array());

                $testItem = $this->FCom_Sales_Model_Order_Item->isItemExist($order->id(), $item['product_id']);
                if ($testItem) {
                    $testItem->update($orderItem);
                } else {
                    $this->FCom_Sales_Model_Order_Item->addNew($orderItem);
                }
            }
        }

        $this->ok();
    }

    public function action_index__DELETE()
    {
        $id = $this->BRequest->param('id');

        if (empty($id)) {
            $this->notFound("Order id is required");
        }

        $order = $this->FCom_Sales_Model_Order->load($id);
        if (!$order) {
            $this->notFound("Order id #{$id} not found");
        }

        $order->delete();
        $this->ok();
    }


}
