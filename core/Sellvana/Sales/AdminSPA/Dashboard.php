<?php

/**
 * Class Sellvana_Sales_AdminSPA_Dashboard
 *
 * @property Sellvana_Sales_Admin_Dashboard Sellvana_Sales_Admin_Dashboard
 */
class Sellvana_Sales_AdminSPA_Dashboard extends BClass
{
    public function widgetOrdersList($filter)
    {
        $orders = $this->Sellvana_Sales_Admin_Dashboard->getOrderRecent();
        foreach ($orders as $o) {
            $o->set('state_overall_label', $o->state()->overall()->getValueLabel());
        }
        return [
            'orders' => $this->BDb->many_as_array($orders),
        ];
    }
    public function widgetOrdersLate($filter)
    {
        $orders = $this->Sellvana_Sales_Admin_Dashboard->getLateOrders();
        $ordersCount = $this->Sellvana_Sales_Admin_Dashboard->getLateOrdersCount();
        return [
            'orders' => $this->BDb->many_as_array($orders),
            'orders_count' => $ordersCount,
        ];
    }
    public function widgetAvgOrderValue($filter)
    {
        return [
            'value' => $this->Sellvana_Sales_Admin_Dashboard->getAvgOrderTotal(),
        ];
    }
    public function widgetOrdersTotals($filter)
    {
        $orderTotal = $this->Sellvana_Sales_Admin_Dashboard->getOrderTotal();
        return [
            'order_total' => $orderTotal,
        ];
    }
    public function widgetTopProducts($filter)
    {
        $products = $this->Sellvana_Sales_Admin_Dashboard->getTopProducts();
        return [
            'products' => $this->BDb->many_as_array($products),
        ];
    }
    public function widgetTopProductsChart($filter)
    {
        $products = $this->Sellvana_Sales_Admin_Dashboard->getTopProducts();
        return [
            'products' => $this->BDb->many_as_array($products),
        ];
    }
}