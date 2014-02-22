<?php

class FCom_Sales_Admin_Controller_Orders extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'orders';
    protected $_modelClass = 'FCom_Sales_Model_Order';
    protected $_gridTitle = 'Orders';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'index'=>'o.id', 'label' => 'Order id', 'width' =>70, 'href'=>BApp::href('orders/form/?id=:id')),
            array('name' => 'admin_name', 'index'=>'o.admin_id', 'label' => 'Assisted by'),
            array('name' => 'create_at', 'index'=>'o.create_at', 'label' => 'Order Date'),
            array('name' => 'billing_name', 'label'=>'Bill to Name', 'index'=>'billing_name'),
            array('name' => 'billing_address', 'label'=>'Bill to Address', 'index'=>'billing_address'),
            array('name' => 'shipping_name', 'label'=>'Ship to Name', 'index'=>'shipping_name'),
            array('name' => 'shipping_address', 'label'=>'Ship to Address', 'index'=>'shipping_address'),
            array('name' => 'grandtotal', 'label'=>'Order Total', 'index'=>'o.grandtotal'),
            array('name' => 'balance', 'label'=>'Paid', 'index'=>'o.balance'),
            array('name' => 'discount', 'label'=>'Discount', 'index'=>'o.coupon_code'),
            //todo: confirm with Boris about status should be stored as id_status
            array('name' => 'status', 'label'=>'Status', 'index'=>'o.status', 'options' => FCom_Sales_Model_Order_Status::i()->statusOptions()),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                  'data' => array('edit' => array('href' => BApp::href($this->_formHref.'?id='), 'col' => 'id'))));
        $config['filters'] = array(
            array('field' => 'create_at', 'type' => 'date-range'),
            array('field' => 'billing_name', 'type' => 'text', 'having' => true),
            array('field' => 'shipping_name', 'type' => 'text', 'having' => true),
            array('field' => 'grandtotal', 'type' => 'number-range'),
            array('field' => 'status', 'type' => 'multiselect'),
        );

        //todo: check this in FCom_Admin_Controller_Abstract_GridForm
        if (!empty($config['orm'])) {
            if (is_string($config['orm'])) {
                $config['orm'] = $config['orm']::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias.'.*');
            }
            $this->gridOrmConfig($config['orm']);
        }
        return $config;
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('FCom_Sales_Model_Order_Address', 'o.id = ab.order_id and ab.atype="billing"', 'ab') //array('o.id','=','a.order_id')
            ->select_expr('CONCAT_WS(" ", ab.firstname,ab.lastname)','billing_name')
            ->select_expr('CONCAT_WS(" \n", ab.street1,ab.city,ab.country,ab.phone)','billing_address');

        $orm->left_outer_join('FCom_Sales_Model_Order_Address', 'o.id = as.order_id and as.atype="shipping"', 'as') //array('o.id','=','a.order_id')
            ->select_expr('CONCAT_WS(" ", as.firstname,as.lastname)','shipping_name')
            ->select_expr('CONCAT_WS(" \n", as.street1,as.city,as.country,as.phone)','shipping_address');

        $orm->left_outer_join('FCom_Admin_Model_User', 'o.admin_id = au.id', 'au')
            ->select_expr('CONCAT_WS(" ", au.firstname,au.lastname)','admin_name');

        $orm->left_outer_join('FCom_Sales_Model_Order_Status', 'o.status = os.code', 'os')
            ->select(array('os_name' => 'os.name'));
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(array(
            'actions' => array(
                'new' => '',
            ),
        ));
    }

    public function action_form()
    {
        $orderId = BRequest::i()->param('id', true);
        $act = BRequest::i()->param('act', true);

        $order = FCom_Sales_Model_Order::i()->load($orderId);
        if (empty($order)) {
            $order = FCom_Sales_Model_Order::i()->create();
        }
        $shipping = FCom_Sales_Model_Order_Address::i()->findByOrder($orderId,'shipping');
        $billing = FCom_Sales_Model_Order_Address::i()->findByOrder($orderId,'billing');
        if ($shipping) {
            $order->shipping_name = $shipping->firstname.' '.$shipping->lastname;
            $order->shipping_address = FCom_Sales_Model_Order_Address::i()->as_html($shipping);
            $order->shipping = $shipping;
        }
        if ($billing) {
            $order->billing_name = $billing->firstname.' '.$billing->lastname;
            $order->billing_address = FCom_Sales_Model_Order_Address::i()->as_html($billing);
            $order->billing = $billing;
        }

        if ($order->customer_id) {
            $customer = FCom_Customer_Model_Customer::i()->load($order->customer_id);
            $customer->guest = false;
        } else {
            $customer = new stdClass();
            $customer->guest = true;
        }
        $order->items = $order->items();
        $order->customer = $customer;

        $model = $order;
        $model->act = $act;

        $view = $this->view($this->_formViewName)->set('model', $model);

        $this->formViewBefore(array('view'=>$view, 'model'=>$model));

        $this->layout($this->_formLayoutName);
        $this->processFormTabs($view, $model, 'edit');
    }

    public function formViewBefore($args)
    {
        $m = $args['model'];
        $act = $m->act;
        if ('edit' == $act) {
            $actions =array(
                'back' => '<a class="btn btn-link" href=\''.BApp::href($this->_gridHref).'\'><span>' .  BLocale::_('Back to list') . '</span></a>',
                'delete' => '<button type="submit" class="st2 sz2 btn btn-danger" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>' .  BLocale::_('Delete') . '</span></button>',
                'save' => '<button type="submit" class="st1 sz2 btn btn-primary" onclick="return adminForm.saveAll(this)"><span>' .  BLocale::_('Save') . '</span></button>',
            );
        } else {
            $actions =array(
                'back' => '<a class="btn btn-link" href=\''.BApp::href($this->_gridHref).'\'><span>Back to list</span></a>',
                'edit' => '<a class="btn btn-primary" href=\''.BApp::href('orders/form').'?id='.$m->id.'&act=edit'.'\'><span>Edit</span></a>',
            );
        }
        if ($m->id) {
            if ($m->act == 'edit') {
                $title = 'Edit Order #'.$m->id;
            } else {
                $title = 'View Order #'.$m->id;
            }
        } else {
            $title = 'Create New Order';
        }
        $args['view']->set(array(
            'form_id' => BLocale::transliterate($this->_formLayoutName),
            'form_url' => BApp::href($this->_formHref).'?id='.$m->id,
            'actions' => $actions,
            'title' => $title,
        ));
        BEvents::i()->fire(static::$_origClass.'::formViewBefore', $args);
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        if ($args['do']!=='DELETE') {
            $order = $args['model'];
            $addrPost = BRequest::i()->post('address');
            if (($newData = BUtil::fromJson($addrPost['data_json']))) {
                $oldModels = FCom_Sales_Model_Order_Address::i()->orm('a')->where('order_id', $order->id)->find_many_assoc();
                foreach ($newData as $data) {
                    if (empty($data['id'])) {
                        continue;
                    }
                    if (!empty($oldModels[$data['id']])) {
                        $addr = $oldModels[$data['id']];
                        $addr->set($data)->save();
                    } elseif ($data['id']<0) {
                        unset($data['id']);
                        $addr = FCom_Sales_Model_Order_Address::i()->newAddress($order->id(), $data);
                    }
                }
            }
            if (($del = BUtil::fromJson($addrPost['del_json']))) {
                FCom_Sales_Model_Order_Address::i()->delete_many(array('id'=>$del, 'order_id'=>$order->id));
            }

            $modelPost = BRequest::i()->post('model');
            $items = $modelPost['items'];
            if ($items) {
                $oldItems = FCom_Sales_Model_Order_Item::i()->orm('i')->where('order_id', $order->id)->find_many_assoc();
                foreach ($items as $id => $itemData) {
                    if (empty($id)) {
                        continue;
                    }

                    if (!empty($itemData['delete'])) {
                        $item = $oldItems[$id];
                        $item->delete();
                    } else if (!empty($oldItems[$id])) {
                        $item = $oldItems[$id];
                        $item->set($itemData)->save();
                    }
                }
            }
        }
    }

    public function itemsOrderGridConfig($order)
    {
        $data = array();
        $items = $order->items();
        if ($items) {
            foreach($items as $item) {
                $product_info = BUtil::fromJson($item->product_info);
                $product = array(
                    'id'           => $item->id,
                    'product_name' => $product_info['product_name'],
                    'local_sku'    => $product_info['local_sku'],
                    'price'        => $product_info['base_price'],
                    'qty'          => $item->qty,
                    'total'        => $item->total,
                );
                $data[] = $product;
            }
        }
        $config = array_merge(
            parent::gridConfig(),
            array(
                'id'        => 'orders_item',
                'data'      => $data,
                'data_mode' => 'local',
                'orm'       => 'FCom_Sales_Model_Order_Item',
                'columns'   => array(
                    //todo: add row for image
                    array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
                    array('name' => 'id', 'label' => 'ID', 'width' => 80, 'hidden' => true),
                    array('name' => 'product_name', 'label' => 'Name', 'width' => 400),
                    array('name' => 'local_sku', 'label' => 'SKU', 'width' => 200),
                    array('name' => 'price', 'label' => 'Price', 'width' => 100),
                    array('name' => 'qty', 'label' => 'Qty', 'width' => 100),
                    array('name' => 'total', 'label' => 'Total', 'width' => 150),
                ),
                'actions'   => array(
                    'add'    => array('caption' => 'Add products'),
                    'delete' => array('caption' => 'Remove') //todo: fix remove is not delete in some grid
                ),
            )
        );
        return array('config' => $config);
    }

    /**
     * get grid config for all orders of customer
     * @param $customer FCom_Customer_Model_Customer
     * @return array
     */
    public function customerOrdersGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_grid_orders_'.$customer->id;
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'index'=>'o.id', 'label' => 'Order id', 'width' =>70),
            array('name' => 'create_at', 'index'=>'o.create_at', 'label' => 'Order Date'),
            array('name' => 'billing_name', 'label'=>'Bill to Name', 'index'=>'ab.billing_name'),
            array('name' => 'billing_address', 'label'=>'Bill to Address', 'index'=>'ab.billing_address'),
            array('name' => 'shipping_name', 'label'=>'Ship to Name', 'index'=>'as.shipping_name'),
            array('name' => 'shipping_address', 'label'=>'Ship to Address', 'index'=>'as.shipping_address'),
            array('name' => 'grandtotal', 'label'=>'Order Total', 'index'=>'o.grandtotal'),
            array('name' => 'balance', 'label'=>'Paid', 'index'=>'o.balance'),
            array('name' => 'discount', 'label'=>'Discount', 'index'=>'o.coupon_code'),
            array('name' => 'status', 'label'=>'Status', 'index'=>'o.status', 'options' => FCom_Sales_Model_Order_Status::i()->statusOptions()),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                  'data' => array('edit' => array('href' => BApp::href($this->_formHref.'?id='), 'col' => 'id'))));
        $config['filters'] = array(
            array('field' => 'create_at', 'type' => 'date-range'),
            array('field' => 'billing_name', 'type' => 'text'),
            array('field' => 'shipping_name', 'type' => 'text'),
            array('field' => 'grandtotal', 'type' => 'number-range'),
            array('field' => 'status', 'type' => 'multiselect'),
        );
        $config['orm'] = $config['orm']->where('customer_id', $customer->id);
        $this->gridOrmConfig($config['orm']);

        return array('config' => $config);
    }
}
