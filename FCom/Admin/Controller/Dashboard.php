<?php

class FCom_Admin_Controller_Dashboard extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $r = BRequest::i();
        if (!$r->xhr()) {
            BResponse::i()->redirect('');
            return;
        }
        $widgets = FCom_Admin_View_Dashboard::i()->getWidgets();
        $widgetKeys = explode(',', $r->get('widgets'));
        $wrapped = $r->get('wrapped');
        $add = $r->get('add');
        $result = array();
        if ($add) {
            $persData = FCom_Admin_Model_User::i()->personalize();
            $pos = 100;
            if (!empty($persData['dashboard']['widgets'])) {
                foreach ($persData['dashboard']['widgets'] as $wKey => $wState) {
                    if (!empty($wState['pos']) && $wState['pos'] > $pos) {
                        $pos++;
                    }
                }
            }
            $persData = array();
        }
        foreach ($widgetKeys as $wKey) {
            if (empty($widgets[$wKey])) {
                continue;
            }
            if (!$wrapped) {
                if (!empty($widgets[$wKey]['view'])) {
                    $html = (string)$this->view($widgets[$wKey]['view']);
                } else {
                    $html = $widgets[$wKey]['content'];
                }
            } else {
                $widgets[$wKey]['async'] = false;
                $html = $this->view('dashboard/widget')->set('widget', $widgets[$wKey])->render();
            }
            $result['widgets'][] = array('key' => $wKey, 'html' => $html);
            if ($add) {
                $persData['dashboard']['widgets'][$wKey]['closed'] = false;
                $persData['dashboard']['widgets'][$wKey]['collapsed'] = false;
                $persData['dashboard']['widgets'][$wKey]['pos'] = ++$pos;
            }
        }
        if ($add && $persData) {
            FCom_Admin_Model_User::i()->personalize($persData);
        }
        BResponse::i()->json($result);
    }

    public function getData($type, $data = false)
    {
        $result = array();
        switch ($type) {
            case 'customerRecent':
                $result = $this->getCustomerRecent();
                break;
            case 'orderRecent':
                $result = $this->getOrderRecent();
                break;
            case 'orderTotal':
                $result = $this->getOrderTotal($data);
                break;
            default: break;
        }
        return $result;
    }

    public function getCustomerRecent()
    {
        $recent = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 7*86400);
        $result = FCom_Customer_Model_Customer::i()->orm()
                ->where_gte('create_at', $recent)
                ->select(array('id' ,'email', 'firstname', 'lastname', 'create_at', 'status'))->find_many();
        return $result;
    }

    public function getOrderRecent()
    {
        $recent = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 7*86400);
        $result = FCom_Sales_Model_Order::i()->orm('o')
            ->join('FCom_Customer_Model_Customer', array('o.customer_id', '=', 'c.id'), 'c')
            ->where_gte('o.create_at', $recent)
            ->select(array('o.*',  'c.firstname', 'c.lastname'))->find_many();
        return $result;
    }

    public function getOrderTotal($filter)
    {
        $orderTotal = FCom_Sales_Model_Order_Status::i()->orm('s')
                  ->left_outer_join('FCom_Sales_Model_Order', array('o.status', '=', 's.name'), 'o')
                  ->group_by('s.id')
                  ->select_expr('COUNT(o.id)', 'order')
                  ->select(array('s.id', 'name'));
        switch ($filter['type']) {
            case 'between':
                $orderTotal = $orderTotal->where_gte('o.create_at', $filter['min'])->where_lte('o.create_at', $filter['max'])->find_many();
                break;
            case 'to':
                $orderTotal = $orderTotal->where_lte('o.create_at', $filter['date'])->find_many();
                break;
            case 'from':
                $orderTotal = $orderTotal->where_gte('o.create_at', $filter['date'])->find_many();
                break;
            case 'equal':
                $orderTotal = $orderTotal->where_like('o.create_at', $filter['date'].'%')->find_many();
                break;
            case 'not_in':
                $orderTotal = $orderTotal->where_raw('o.create_at', 'NOT BETWEEN ? AND ?', $filter['min'], $filter['max'])->find_many();
                break;
            default:
                $orderTotal = $orderTotal->find_many();
                break;
        }

        return $orderTotal;
    }

    public function action_index__POST()
    {
        $p = BRequest::i()->post();
        if ($p['range'] == 'range') {
            switch ($p['date']) {
                case 'last-month':
                    $p['min'] = date("Y-m-1", strtotime("last month"));
                    $p['max'] = date("Y-m-t", strtotime("last month"));
                    break;
                case 'last-week':
                    $p['min'] = date("Y-m-d", strtotime("last week"));
                    $p['max'] = date("Y-m-d", strtotime("last week + 7 days"));
                    break;
                case 'today':
                    $p['date'] = date("Y-m-d");
                    break;
                case 'all':
                    break;
                default:
                    $tmp = explode('~', $p['date']);
                    $p['min'] = $tmp[0];
                    $p['max'] = $tmp[1];
                    break;
            }
        }
        $result = $this->getData('orderTotal', $p);
        BResponse::i()->json($result);
    }
}
