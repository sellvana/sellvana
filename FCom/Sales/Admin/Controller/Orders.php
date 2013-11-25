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
            array('name' => 'create_at', 'index'=>'o.create_at', 'label' => 'Purchased on'),
            array('name' => 'billing_name', 'label'=>'Bill to Name', 'index'=>'ab.billing_name'),
            array('name' => 'billing_address', 'label'=>'Bill to Address', 'index'=>'ab.billing_address'),
            array('name' => 'shipping_name', 'label'=>'Ship to Name', 'index'=>'as.shipping_name'),
            array('name' => 'shipping_address', 'label'=>'Ship to Address', 'index'=>'as.shipping_address'),
            array('name' => 'grandtotal', 'label'=>'GT (base)', 'index'=>'o.grandtotal'),
            array('name' => 'balance', 'label'=>'GT (paid)', 'index'=>'o.balance'),
            array('name' => 'discount', 'label'=>'Discount', 'index'=>'o.coupon_code'),
            //todo: confirm with Boris about status should be stored as id_status
            array('name' => 'status', 'label'=>'Status', 'index'=>'o.status', 'options' => FCom_Sales_Model_Order_Status::i()->statusOptions()),
        );
        $config['filters'] = array(
            array('field' => 'create_at', 'type' => 'date-range'),
            array('field' => 'billing_name', 'type' => 'text'),
            array('field' => 'shipping_name', 'type' => 'text'),
            array('field' => 'grandtotal', 'type' => 'text'), //todo: filter type compare, eg: > 1000
            array('field' => 'status', 'type' => 'select'),
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

        $orm->left_outer_join('FCom_Sales_Model_Order_Status', 'o.status = os.code', 'os')
            ->select(array('os_name' => 'os.name'));
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $args['view']->set(array(
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
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href($this->_gridHref).'\'"><span>' .  BLocale::_('Back to list') . '</span></button>',
                'delete' => '<button type="submit" class="st2 sz2 btn" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>' .  BLocale::_('Delete') . '</span></button>',
                'save' => '<button type="submit" class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>' .  BLocale::_('Save') . '</span></button>',
            );
        } else {
            $actions =array(
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href($this->_gridHref).'\'"><span>Back to list</span></button>',
                'edit' => '<button type="button" class="st1 sz2 btn" onclick="location.href=\''.BApp::href('orders/form').'?id='.$m->id.'&act=edit'.'\'"><span>Edit</span></button>',
            );
        }
        $args['view']->set(array(
            'form_id' => BLocale::transliterate($this->_formLayoutName),
            'form_url' => BApp::href($this->_formHref).'?id='.$m->id,
            'actions' => $actions,
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

    public function action_view()
    {
        //todo: view order detail
    }
}
