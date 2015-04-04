<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Admin_Controller_Orders
 *
 * @property FCom_Core_Model_Seq $FCom_Core_Model_Seq
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 * @property Sellvana_Sales_Model_Order_State_Overall $Sellvana_Sales_Model_Order_State_Overall
 * @property Sellvana_Sales_Model_Order_State_Payment $Sellvana_Sales_Model_Order_State_Payment
 * @property Sellvana_Sales_Model_Order_State_Delivery $Sellvana_Sales_Model_Order_State_Delivery
 * @property Sellvana_Sales_Model_Order_State_Custom $Sellvana_Sales_Model_Order_State_Custom
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 * @property Sellvana_Sales_Model_Order_Shipment $Sellvana_Sales_Model_Order_Shipment
 * @property Sellvana_Sales_Model_Order_Return $Sellvana_Sales_Model_Order_Return
 * @property Sellvana_Sales_Model_Order_Refund $Sellvana_Sales_Model_Order_Refund
 * @property Sellvana_Sales_Model_Order_Comment $Sellvana_Sales_Model_Order_Comment
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 */

class Sellvana_Sales_Admin_Controller_Orders extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'orders';
    protected $_modelClass = 'Sellvana_Sales_Model_Order';
    protected $_gridTitle = 'Orders';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'sales/orders';
    protected $_navPath = 'sales/orders';

    public function gridConfig()
    {
        $overallStates = $this->Sellvana_Sales_Model_Order_State_Overall->getAllValueLabels();
        $paymentStates = $this->Sellvana_Sales_Model_Order_State_Payment->getAllValueLabels();
        $deliveryStates = $this->Sellvana_Sales_Model_Order_State_Delivery->getAllValueLabels();
        $customStates = $this->Sellvana_Sales_Model_Order_State_Custom->getAllValueLabels();

        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'index' => 'o.id', 'label' => 'Order id', 'width' => 70,
                'href' => $this->BApp->href('orders/form/?id=:id')],
            ['name' => 'admin_name', 'index' => 'o.admin_id', 'label' => 'Assisted by'],
            ['name' => 'create_at', 'index' => 'o.create_at', 'label' => 'Order Date'],

            ['name' => 'billing_firstname', 'label' => 'Bill First Name', 'index' => 'billing_firstname'],
            ['name' => 'billing_lastname', 'label' => 'Bill Last Name', 'index' => 'billing_lastname'],
            ['name' => 'billing_city', 'label' => 'Bill City', 'index' => 'billing_city'],
            ['name' => 'billing_postcode', 'label' => 'Bill Zip', 'index' => 'billing_postcode'],
            ['name' => 'billing_region', 'label' => 'Bill State/Province', 'index' => 'billing_region'],
            ['name' => 'billing_country', 'label' => 'Bill Country', 'index' => 'billing_country'],

            ['name' => 'shipping_firstname', 'label' => 'Ship First Name', 'index' => 'shipping_firstname'],
            ['name' => 'shipping_lastname', 'label' => 'Ship Last Name', 'index' => 'shipping_lastname'],
            ['name' => 'shipping_city', 'label' => 'Ship City', 'index' => 'shipping_city'],
            ['name' => 'shipping_postcode', 'label' => 'Ship Zip', 'index' => 'shipping_postcode'],
            ['name' => 'shipping_region', 'label' => 'Ship State/Province', 'index' => 'shipping_region'],
            ['name' => 'shipping_country', 'label' => 'Ship Country', 'index' => 'shipping_country'],

            ['name' => 'shipping_name', 'label' => 'Ship to Name', 'index' => 'shipping_name'],
            ['name' => 'shipping_address', 'label' => 'Ship to Address', 'index' => 'shipping_address'],
            ['name' => 'grand_total', 'label' => 'Order Total', 'index' => 'o.grand_total'],
            ['name' => 'amount_due', 'label' => 'Due', 'index' => 'o.amount_due'],
            ['name' => 'amount_paid', 'label' => 'Paid', 'index' => 'o.amount_paid'],
            ['name' => 'discount', 'label' => 'Discount', 'index' => 'o.coupon_code'],

            ['name' => 'state_overall', 'label' => 'Overall State', 'index' => 'o.state_overall', 'options' => $overallStates],
            ['name' => 'state_payment', 'label' => 'Payment State', 'index' => 'o.state_payment', 'options' => $paymentStates],
            ['name' => 'state_delivery', 'label' => 'Delivery State', 'index' => 'o.state_delivery', 'options' => $deliveryStates],
            ['name' => 'state_custom', 'label' => 'Custom State', 'index' => 'o.state_custom', 'options' => $customStates],

            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
            ]],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'billing_name', 'type' => 'text', 'having' => true],
            ['field' => 'shipping_name', 'type' => 'text', 'having' => true],
            ['field' => 'grand_total', 'type' => 'number-range'],
            ['field' => 'state_overall', 'type' => 'multiselect'],
            ['field' => 'state_payment', 'type' => 'multiselect'],
            ['field' => 'state_delivery', 'type' => 'multiselect'],
            ['field' => 'state_custom', 'type' => 'multiselect'],
        ];

        return $config;
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->select($this->_mainTableAlias . '.*');

        $orm->left_outer_join('FCom_Admin_Model_User', 'o.admin_id = au.id', 'au')
            ->select_expr('CONCAT_WS(" ", au.firstname,au.lastname)', 'admin_name');
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

        $order = $this->Sellvana_Sales_Model_Order->load($orderId);
        if (empty($order)) {
            $order = $this->Sellvana_Sales_Model_Order->create();
        }

        if ($order->customer_id) {
            $customer = $this->Sellvana_Customer_Model_Customer->load($order->customer_id);
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
        $actions = [
            'back' => '<a class="btn btn-link" href=\'' . $this->BApp->href($this->_gridHref) . '\'><span>'
                . $this->BLocale->_('Back to list') . '</span></a>',
            'delete' => '<button type="submit" class="st2 sz2 btn btn-danger" name="do" value="DELETE" '
                . 'onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>'
                . $this->BLocale->_('Delete') . '</span></button>',
            'save' => '<button type="submit" class="st1 sz2 btn btn-primary" onclick="return adminForm.saveAll(this)"><span>'
                . $this->BLocale->_('Save') . '</span></button>',
        ];
        if ($m->id) {
            if ($m->act == 'edit') {
                $title = 'Edit Order #' . $m->get('unique_id');
            } else {
                $title = 'View Order #' . $m->get('unique_id');
            }
        } else {
            $title = 'Create New Order';
        }
        $info = $this->_('Grand Total') . ': ' . $this->BLocale->currency($m->get('grand_total'))
            . ' | ' . $this->_('Overall Status') . ': ' . $m->state()->overall()->getValueLabel()
            . ' | ' . $this->_('Payment') . ': ' . $m->state()->payment()->getValueLabel()
            . ' | ' . $this->_('Delivery') . ': ' . $m->state()->delivery()->getValueLabel();
        $customState = $m->state()->custom()->getValueLabel();
        if ($customState) {
            $info .= ' | ' . $this->_('Custom Status') . ' ' . $customState;
        }
        $args['view']->set([
            'form_id' => $this->BLocale->transliterate($this->_formLayoutName),
            'form_url' => $this->BApp->href($this->_formHref) . '?id=' . $m->id,
            'actions' => $actions,
            'otherInfo' => $m->id ? $info : '',
            'title' => $title,
        ]);
        $this->BEvents->fire(static::$_origClass . '::formViewBefore', $args);
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        if ($args['do'] !== 'DELETE') {
            $order = $args['model'];

            $orderPost = $this->BRequest->post('order');
            $order->set($orderPost);

            $orderStatePost = $this->BRequest->post('order_state');
            if (!empty($orderStatePost['custom'])) {
                $order->state()->custom()->changeState($orderStatePost['custom']);
            }

            $order->save();

            $itemsPost = $this->BRequest->post('items');
            if ($itemsPost) {
                $oldItems = $this->Sellvana_Sales_Model_Order_Item->orm('i')->where('order_id', $order->id())
                    ->find_many_assoc();
                foreach ($itemsPost as $id => $itemData) {
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
        $config = array_merge(
            parent::gridConfig(),
            [
                'id'        => 'orders_item',
                'data'      => $order->items(),
                'data_mode' => 'local',
                'orm'       => 'Sellvana_Sales_Model_Order_Item',
                'columns'   => [
                    //todo: add row for image
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'width' => 80, 'hidden' => true],
                    ['name' => 'product_name', 'label' => 'Name', 'width' => 400],
                    ['name' => 'product_sku', 'label' => 'SKU', 'width' => 200],
                    ['name' => 'price', 'label' => 'Price', 'width' => 100],
                    ['name' => 'qty_ordered', 'label' => 'Qty', 'width' => 100],
                    ['name' => 'row_total', 'label' => 'Total', 'width' => 150],
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
     * @param $customer Sellvana_Customer_Model_Customer
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
                'options' => $this->Sellvana_Sales_Model_StateCustom->optionsByType('order')],
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
        $dayRecent = ($this->BConfig->get('modules/Sellvana_Sales/recent_day')) ? $this->BConfig->get('modules/Sellvana_Sales/recent_day') : 7;
        $recent = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - $dayRecent * 86400);
        $result = $this->Sellvana_Sales_Model_Order->orm('o')
            ->join('Sellvana_Customer_Model_Customer', ['o.customer_id', '=', 'c.id'], 'c')
            ->where_gte('o.create_at', $recent)
            ->select(['o.*',  'c.firstname', 'c.lastname'])->find_many();

        return $result;
    }

    public function getOrderTotal($filter)
    {
        /*
        // TODO: redo the whole thing
        $orderTotal = $this->Sellvana_Sales_Model_Order_CustomStatus->orm('s')
            ->left_outer_join('Sellvana_Sales_Model_Order', ['o.status', '=', 's.name'], 'o')
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
            if (isset($r['modules']['Sellvana_Sales']['order_number'])) {
                $orderNumber = '1' . $r['modules']['Sellvana_Sales']['order_number'];
                $configOrderNumber = $this->BConfig->get('modules/Sellvana_Sales/order_number');
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
        if (isset($args['post']['config']['modules']['Sellvana_Sales']['order_number'])) {
            $seq = $this->FCom_Core_Model_Seq->orm()->where('entity_type', 'order')->find_one();
            $configOrderNumber = $this->BConfig->get('modules/Sellvana_Sales/order_number');
            $orderNumber = $args['post']['config']['modules']['Sellvana_Sales']['order_number'];
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
            $result = $this->Sellvana_Sales_Model_Order->orm()
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

    public function paymentsGridConfig($model)
    {
        $orm = $this->Sellvana_Sales_Model_Order_Payment->orm('s')
            ->select('s.*')->where('order_id', $model->id());

        $config = [
            'id' => 'order_payments',
            'orm' => $orm,
            'data_mode' => 'local',
            //'caption'      =>$caption,
            'columns' => [
                ['type' => 'row_select'],
                ['name' => 'id', 'label' => 'ID'],
            ],
            'actions' => [
                'add' => ['caption' => 'Add payment'],
                'delete' => ['caption' => 'Remove']
            ],
            'filters' => [
                ['field' => 'state_overall', 'type' => 'text'],
                ['field' => 'state_delivery', 'type' => 'text'],
            ],
            'events' => ['init', 'add', 'mass-delete'],
            'grid_before_create' => 'order_payments_register',
        ];

        return ['config' => $config];
    }

    public function shipmentsGridConfig($model)
    {
        $orm = $this->Sellvana_Sales_Model_Order_Shipment->orm('s')
            ->select('s.*')->where('order_id', $model->id());

        $config = [
            'id' => 'order_shipments',
            'orm' => $orm,
            'data_mode' => 'local',
            //'caption'      =>$caption,
            'columns' => [
                ['type' => 'row_select'],
                ['name' => 'id', 'label' => 'ID'],
            ],
            'actions' => [
                'add' => ['caption' => 'Add shipment'],
                'delete' => ['caption' => 'Remove']
            ],
            'filters' => [
                ['field' => 'state_overall', 'type' => 'text'],
                ['field' => 'state_delivery', 'type' => 'text'],
            ],
            'events' => ['init', 'add', 'mass-delete'],
            'grid_before_create' => 'order_shipments_register',
        ];

        return ['config' => $config];
    }

    public function returnsGridConfig($model)
    {
        $orm = $this->Sellvana_Sales_Model_Order_Return->orm('s')
            ->select('s.*')->where('order_id', $model->id());

        $config = [
            'id' => 'order_returns',
            'orm' => $orm,
            'data_mode' => 'local',
            //'caption'      =>$caption,
            'columns' => [
                ['type' => 'row_select'],
                ['name' => 'id', 'label' => 'ID'],
            ],
            'actions' => [
                'add' => ['caption' => 'Add return'],
                'delete' => ['caption' => 'Remove']
            ],
            'filters' => [
                ['field' => 'state_overall', 'type' => 'text'],
                ['field' => 'state_delivery', 'type' => 'text'],
            ],
            'events' => ['init', 'add', 'mass-delete'],
            'grid_before_create' => 'order_returns_register',
        ];

        return ['config' => $config];
    }

    public function refundsGridConfig($model)
    {
        $orm = $this->Sellvana_Sales_Model_Order_Refund->orm('s')
            ->select('s.*')->where('order_id', $model->id());

        $config = [
            'id' => 'order_refunds',
            'orm' => $orm,
            'data_mode' => 'local',
            //'caption'      =>$caption,
            'columns' => [
                ['type' => 'row_select'],
                ['name' => 'id', 'label' => 'ID'],
            ],
            'actions' => [
                'add' => ['caption' => 'Add refund'],
                'delete' => ['caption' => 'Remove']
            ],
            'filters' => [
                ['field' => 'state_overall', 'type' => 'text'],
                ['field' => 'state_delivery', 'type' => 'text'],
            ],
            'events' => ['init', 'add', 'mass-delete'],
            'grid_before_create' => 'order_refunds_register',
        ];

        return ['config' => $config];
    }

    public function commentsGridConfig($model)
    {
        $orm = $this->Sellvana_Sales_Model_Order_Comment->orm('s')
            ->select('s.*')->where('order_id', $model->id());

        $config = [
            'id' => 'order_comments',
            'orm' => $orm,
            'data_mode' => 'local',
            //'caption'      =>$caption,
            'columns' => [
                ['type' => 'row_select'],
                ['name' => 'id', 'label' => 'ID'],
            ],
            'actions' => [
                'add' => ['caption' => 'Add comment'],
                'delete' => ['caption' => 'Remove']
            ],
            'filters' => [
                ['field' => 'state_overall', 'type' => 'text'],
                ['field' => 'state_delivery', 'type' => 'text'],
            ],
            'events' => ['init', 'add', 'mass-delete'],
            'grid_before_create' => 'order_comments_register',
        ];

        return ['config' => $config];
    }

    public function historyGridConfig($model)
    {
        $orm = $this->Sellvana_Sales_Model_Order_History->orm('s')
            ->select('s.*')->where('order_id', $model->id());

        $config = [
            'id' => 'order_history',
            'orm' => $orm,
            'data_mode' => 'local',
            //'caption'      =>$caption,
            'columns' => [
                ['type' => 'row_select'],
                ['name' => 'id', 'label' => 'ID'],
            ],
            'filters' => [
                ['field' => 'state_overall', 'type' => 'text'],
                ['field' => 'state_delivery', 'type' => 'text'],
            ],
            'events' => ['init', 'add', 'mass-delete'],
            'grid_before_create' => 'order_history_register',
        ];

        return ['config' => $config];
    }
}

