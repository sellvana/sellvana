<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Admin_Controller_Orders
 *
 * @property FCom_Core_Model_Seq $FCom_Core_Model_Seq
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 * @property FCom_Sales_Model_Order_Address $FCom_Sales_Model_Order_Address
 * @property FCom_Sales_Model_Order_CustomStatus $FCom_Sales_Model_Order_CustomStatus
 * @property FCom_Sales_Model_Order_Item $FCom_Sales_Model_Order_Item
 * @property FCom_Sales_Model_Order_StateCustom $FCom_Sales_Model_Order_StateCustom
 */

class FCom_Sales_Admin_Controller_Orders extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'orders';
    protected $_modelClass = 'FCom_Sales_Model_Order';
    protected $_gridTitle = 'Orders';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'sales/orders';
    protected $_navPath = 'sales/orders';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'index' => 'o.id', 'label' => 'Order id', 'width' => 70,
                'href' => $this->BApp->href('orders/form/?id=:id')],
            ['name' => 'admin_name', 'index' => 'o.admin_id', 'label' => 'Assisted by'],
            ['name' => 'create_at', 'index' => 'o.create_at', 'label' => 'Order Date'],
            ['name' => 'billing_name', 'label' => 'Bill to Name', 'index' => 'billing_name'],
            ['name' => 'billing_address', 'label' => 'Bill to Address', 'index' => 'billing_address'],
            ['name' => 'shipping_name', 'label' => 'Ship to Name', 'index' => 'shipping_name'],
            ['name' => 'shipping_address', 'label' => 'Ship to Address', 'index' => 'shipping_address'],
            ['name' => 'grand_total', 'label' => 'Order Total', 'index' => 'o.grand_total'],
            ['name' => 'amount_due', 'label' => 'Paid', 'index' => 'o.amount_due'],
            ['name' => 'discount', 'label' => 'Discount', 'index' => 'o.coupon_code'],
            //todo: confirm with Boris about status should be stored as id_status
            ['name' => 'status', 'label' => 'Status', 'index' => 'o.status',
                'options' => $this->FCom_Sales_Model_Order_StateCustom->optionsByType('order')],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
            ]],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'billing_name', 'type' => 'text', 'having' => true],
            ['field' => 'shipping_name', 'type' => 'text', 'having' => true],
            ['field' => 'grand_total', 'type' => 'number-range'],
            ['field' => 'status', 'type' => 'multiselect'],
        ];

        //todo: check this in FCom_Admin_Controller_Abstract_GridForm
        if (!empty($config['orm'])) {
            if (is_string($config['orm'])) {
                $config['orm'] = $this->{$config['orm']}->orm($this->_mainTableAlias)->select($this->_mainTableAlias . '.*');
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
            ->select_expr('CONCAT_WS(" ", ab.firstname,ab.lastname)', 'billing_name')
            ->select_expr('CONCAT_WS(" \n", ab.street1,ab.city,ab.country,ab.phone)', 'billing_address');

        $orm->left_outer_join('FCom_Sales_Model_Order_Address', 'o.id = as.order_id and as.atype="shipping"', 'as') //array('o.id','=','a.order_id')
            ->select_expr('CONCAT_WS(" ", as.firstname,as.lastname)', 'shipping_name')
            ->select_expr('CONCAT_WS(" \n", as.street1,as.city,as.country,as.phone)', 'shipping_address');

        $orm->left_outer_join('FCom_Admin_Model_User', 'o.admin_id = au.id', 'au')
            ->select_expr('CONCAT_WS(" ", au.firstname,au.lastname)', 'admin_name');

        $orm->left_outer_join('FCom_Sales_Model_Order_CustomStatus', 'o.status = os.code', 'os')
            ->select(['os_name' => 'os.name']);
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set([
            'actions' => [
                'new' => '',
            ],
        ]);
    }

    public function action_form()
    {
        $orderId = $this->BRequest->param('id', true);
        $act = $this->BRequest->param('act', true);

        $order = $this->FCom_Sales_Model_Order->load($orderId);
        if (empty($order)) {
            $order = $this->FCom_Sales_Model_Order->create();
        }
        $shipping = $this->FCom_Sales_Model_Order_Address->findByOrder($orderId, 'shipping');
        $billing = $this->FCom_Sales_Model_Order_Address->findByOrder($orderId, 'billing');
        if ($shipping) {
            $order->shipping_name = $shipping->firstname . ' ' . $shipping->lastname;
            $order->shipping_address = $order->addressAsHtml('shipping');
            $order->shipping = $shipping;
        }
        if ($billing) {
            $order->billing_name = $billing->firstname . ' ' . $billing->lastname;
            $order->billing_address = $order->addressAsHtml('billing');
            $order->billing = $billing;
        }

        if ($order->customer_id) {
            $customer = $this->FCom_Customer_Model_Customer->load($order->customer_id);
            $customer->guest = false;
        } else {
            $customer = new stdClass();
            $customer->guest = true;
        }
        $order->items = $order->items();
        $order->customer = $customer;

        $model = $order;
        $model->act = $act;

        $this->layout($this->_formLayoutName);
        $view = $this->view($this->_formViewName)->set('model', $model);

        $this->formViewBefore(['view' => $view, 'model' => $model]);

        $this->processFormTabs($view, $model, 'edit');
    }

    public function formViewBefore($args)
    {
        $m = $args['model'];
        $act = $m->act;
        if ('edit' == $act) {
            $actions = [
                'back' => '<a class="btn btn-link" href=\'' . $this->BApp->href($this->_gridHref) . '\'><span>'
                    . $this->BLocale->_('Back to list') . '</span></a>',
                'delete' => '<button type="submit" class="st2 sz2 btn btn-danger" name="do" value="DELETE" '
                    . 'onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>'
                    . $this->BLocale->_('Delete') . '</span></button>',
                'save' => '<button type="submit" class="st1 sz2 btn btn-primary" onclick="return adminForm.saveAll(this)"><span>'
                    . $this->BLocale->_('Save') . '</span></button>',
            ];
        } else {
            $actions = [
                'back' => '<a class="btn btn-link" href=\'' . $this->BApp->href($this->_gridHref) . '\'><span>Back to list</span></a>',
                'edit' => '<a class="btn btn-primary" href=\'' . $this->BApp->href('orders/form') . '?id=' . $m->id . '&act=edit' . '\'><span>Edit</span></a>',
            ];
        }
        if ($m->id) {
            if ($m->act == 'edit') {
                $title = 'Edit Order #' . $m->id;
            } else {
                $title = 'View Order #' . $m->id;
            }
        } else {
            $title = 'Create New Order';
        }
        $args['view']->set([
            'form_id' => $this->BLocale->transliterate($this->_formLayoutName),
            'form_url' => $this->BApp->href($this->_formHref) . '?id=' . $m->id,
            'actions' => $actions,
            'title' => $title,
        ]);
        $this->BEvents->fire(static::$_origClass . '::formViewBefore', $args);
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        if ($args['do'] !== 'DELETE') {
            $order = $args['model'];
            $addrPost = $this->BRequest->post('address');
            if (($newData = $this->BUtil->fromJson($addrPost['data_json']))) {
                $oldModels = $this->FCom_Sales_Model_Order_Address->orm('a')->where('order_id', $order->id())
                    ->find_many_assoc();
                foreach ($newData as $data) {
                    if (empty($data['id'])) {
                        continue;
                    }
                    if (!empty($oldModels[$data['id']])) {
                        $addr = $oldModels[$data['id']];
                        $addr->set($data)->save();
                    } elseif ($data['id'] < 0) {
                        unset($data['id']);
                        $addr = $this->FCom_Sales_Model_Order_Address->newAddress($order->id(), $data);
                    }
                }
            }
            if (($del = $this->BUtil->fromJson($addrPost['del_json']))) {
                $this->FCom_Sales_Model_Order_Address->delete_many(['id' => $del, 'order_id' => $order->id()]);
            }

            $modelPost = $this->BRequest->post('model');
            $items = $modelPost['items'];
            if ($items) {
                $oldItems = $this->FCom_Sales_Model_Order_Item->orm('i')->where('order_id', $order->id())
                    ->find_many_assoc();
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
        $data = [];
        $items = $order->items();
        if ($items) {
            foreach ($items as $item) {
                $product_info = $this->BUtil->fromJson($item->product_info);
                $product = [
                    'id'           => $item->id,
                    'product_name' => $product_info['product_name'],
                    'product_sku'    => $product_info['product_sku'],
                    'price'        => $product_info['base_price'],
                    'qty'          => $item->qty,
                    'total'        => $item->total,
                ];
                $data[] = $product;
            }
        }
        $config = array_merge(
            parent::gridConfig(),
            [
                'id'        => 'orders_item',
                'data'      => $data,
                'data_mode' => 'local',
                'orm'       => 'FCom_Sales_Model_Order_Item',
                'columns'   => [
                    //todo: add row for image
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 80, 'hidden' => true],
                    ['name' => 'product_name', 'label' => 'Name', 'width' => 400],
                    ['name' => 'product_sku', 'label' => 'SKU', 'width' => 200],
                    ['name' => 'price', 'label' => 'Price', 'width' => 100],
                    ['name' => 'qty', 'label' => 'Qty', 'width' => 100],
                    ['name' => 'total', 'label' => 'Total', 'width' => 150],
                ],
                'actions'   => [
                    'add'    => ['caption' => 'Add products'],
                    'delete' => ['caption' => 'Remove'] //todo: fix remove is not delete in some grid
                ],
            ]
        );
        return ['config' => $config];
    }

    /**
     * get grid config for all orders of customer
     * @param $customer FCom_Customer_Model_Customer
     * @return array
     */
    public function customerOrdersGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_grid_orders_' . $customer->id();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'index' => 'o.id', 'label' => 'Order id', 'width' => 70],
            ['name' => 'create_at', 'index' => 'o.create_at', 'label' => 'Order Date'],
            ['name' => 'billing_name', 'label' => 'Bill to Name', 'index' => 'ab.billing_name'],
            ['name' => 'billing_address', 'label' => 'Bill to Address', 'index' => 'ab.billing_address'],
            ['name' => 'shipping_name', 'label' => 'Ship to Name', 'index' => 'as.shipping_name'],
            ['name' => 'shipping_address', 'label' => 'Ship to Address', 'index' => 'as.shipping_address'],
            ['name' => 'grand_total', 'label' => 'Order Total', 'index' => 'o.grand_total'],
            ['name' => 'amount_due', 'label' => 'Paid', 'index' => 'o.amount_due'],
            ['name' => 'discount', 'label' => 'Discount', 'index' => 'o.coupon_code'],
            ['name' => 'status', 'label' => 'Status', 'index' => 'o.status',
                'options' => $this->FCom_Sales_Model_Order_StateCustom->optionsByType('order')],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
            ]],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'billing_name', 'type' => 'text'],
            ['field' => 'shipping_name', 'type' => 'text'],
            ['field' => 'grand_total', 'type' => 'number-range'],
            ['field' => 'status', 'type' => 'multiselect'],
        ];
        $config['orm'] = $config['orm']->where('customer_id', $customer->id());
        $this->gridOrmConfig($config['orm']);

        return ['config' => $config];
    }

    public function getOrderRecent()
    {
        $dayRecent = ($this->BConfig->get('modules/FCom_Sales/recent_day')) ? $this->BConfig->get('modules/FCom_Sales/recent_day') : 7;
        $recent = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - $dayRecent * 86400);
        $result = $this->FCom_Sales_Model_Order->orm('o')
            ->join('FCom_Customer_Model_Customer', ['o.customer_id', '=', 'c.id'], 'c')
            ->where_gte('o.create_at', $recent)
            ->select(['o.*',  'c.firstname', 'c.lastname'])->find_many();

        return $result;
    }

    public function getOrderTotal($filter)
    {
        /*
        // TODO: redo the whole thing
        $orderTotal = $this->FCom_Sales_Model_Order_CustomStatus->orm('s')
            ->left_outer_join('FCom_Sales_Model_Order', ['o.status', '=', 's.name'], 'o')
            ->group_by('s.id')
            ->select_expr('COUNT(o.id)', 'order')
            ->select(['s.id', 'name']);
        $tmp = $result = $orderTotal->find_many();
        $tmp = [];
        switch ($filter['type']) {
            case 'between':
                $tmp = $orderTotal->where_gte('o.create_at', $filter['min'])->where_lte('o.create_at', $filter['max'])->find_many();
                break;
            case 'to':
                $tmp = $orderTotal->where_lte('o.create_at', $filter['date'])->find_many();
                break;
            case 'from':
                $tmp = $orderTotal->where_gte('o.create_at', $filter['date'])->find_many();
                break;
            case 'equal':
                $tmp = $orderTotal->where_like('o.create_at', $filter['date'] . '%')->find_many();
                break;
            case 'not_in':
                $tmp = $orderTotal->where_raw('o.create_at', 'NOT BETWEEN ? AND ?', $filter['min'], $filter['max'])->find_many();
                break;
            default:
                break;
        }
        foreach ($result as $obj) {
            $order = 0;
            foreach ($tmp as $key) {
                if ($obj->get('id') == $key->get('id')) {
                    $order = $key->get('order');
                }
            }
            $obj->set('order', $order);
        }
        */
        $result = [];
        return $result;
    }

    public function action_validate_order_number__POST()
    {
        $r = $this->BRequest->post('config');
        $seq = $this->FCom_Core_Model_Seq->orm()->where('entity_type', 'order')->find_one();
        $result = ['status' => true, 'messages' => ''];
        if ($seq) {
            if (isset($r['modules']['FCom_Sales']['order_number'])) {
                $orderNumber = '1' . $r['modules']['FCom_Sales']['order_number'];
                $configOrderNumber = $this->BConfig->get('modules/FCom_Sales/order_number');
                if ($configOrderNumber != null) {
                    $configOrderNumber = '1' . $configOrderNumber;
                }
                if ($configOrderNumber && $orderNumber != $configOrderNumber  && $orderNumber < $seq->current_seq_id) {
                    $result['status'] = false;
                    $result['messages'] = $this->BLocale->_('Order number must larger than order current: ' . $seq->current_seq_id);
                }
            }
        }
        $this->BResponse->json($result);
    }

    public function onSaveAdminSettings($args)
    {
        if (isset($args['post']['config']['modules']['FCom_Sales']['order_number'])) {
            $seq = $this->FCom_Core_Model_Seq->orm()->where('entity_type', 'order')->find_one();
            $configOrderNumber = $this->BConfig->get('modules/FCom_Sales/order_number');
            $orderNumber = $args['post']['config']['modules']['FCom_Sales']['order_number'];
            if ($seq && ($configOrderNumber != null || $orderNumber != $configOrderNumber)) {
                $seq->set('current_seq_id', '1' . $orderNumber)->save();
            }
        }
    }

    public function onHeaderSearch($args)
    {
        $r = $this->BRequest->get();
        if (isset($r['q']) && $r['q'] != '') {
            $value = '%' . (string)$r['q'] . '%';
            $result = $this->FCom_Sales_Model_Order->orm()
                ->where(['OR' => [
                    ['id like ?', $value],
                    ['customer_email like ?', $value],
                    ['unique_id like ?', $value],
                    ['coupon_code like ?', $value],
                ]])->find_one();
            $args['result']['order'] = null;
            if ($result) {
                $args['result']['order'] = [
                    'priority' => 20,
                    'url' => $this->BApp->href($this->_formHref) . '?id=' . $result->id()
                ];
            }
        }
    }
}
