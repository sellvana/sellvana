<?php

/**
 * Class Sellvana_GoogleApi_Frontend
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_GoogleApi_Frontend extends BClass
{
    public function getProductListData(BView $view = null)
    {
        if ($view && $view->get('products')) {
            $products = $view->get('products');
        } elseif ($this->BApp->get('products_data')) {
            $productsData = $this->BApp->get('products_data');
            $products = $productsData['rows'];
        }

        if (empty($products)) {
            return ['items' => []];
        }

        /** @var Sellvana_Catalog_Model_Category $category */
        $category = $this->BApp->get('current_category');

        $items = [];
        $pos = 1;
        /** @var Sellvana_Catalog_Model_Product $product */
        foreach ($products as $product) {
            $item = [
                'id' => $product->get('product_sku'),
                'name' => $product->get('product_name'),
                'position' => $pos++,
            ];
            if ($category) {
                $item['category'] = $category->get('full_name');
            }
            if ($product->get('brand')) {
                $item['brand'] = $product->get('brand');
            }
            //'list' => $view ? $view->get('list_name') : null,
            //'variant' => null,
            //'dimension1' => null,

            $items[$product->id()] = $item;
        }

        return [
            'items' => $items,
        ];
    }

    public function getProductData()
    {
        /** @var Sellvana_Catalog_Model_Product $product */
        $product = $this->BApp->get('current_product');

        /** @var Sellvana_Catalog_Model_Category $category */
        $category = $this->BApp->get('current_category');

        $item = [
            'id' => $product->get('product_sku'),
            'name' => $product->get('product_name'),
            'price' => $product->getCatalogPrice(), //TODO: calculate variant price in JS
            'quantity' => 1,
        ];
        if ($category) {
            $item['category'] = $category->get('full_name');
        }
        if ($product->get('brand')) {
            $item['brand'] = $product->get('brand');
        }

        return [
            'item' => $item,
            'id' => $product->id(),
        ];
    }

    public function getCartData()
    {
        $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();

        $items = [];

        foreach ($cart->items() as $ci) {
            $item = [
                'id' => $ci->get('product_sku'),
                'name' => $ci->get('product_name'),
                //'category' => null, //TODO: implement cart/order original product category tracking?
                //'brand' => null,
                'price' => $ci->get('price'),
                'quantity' => $ci->get('qty'),
            ];
            if ($ci->getData('variant')) {
                $item['variant'] = $this->BUtil->toJson($ci->getData('variant'));
            }
            $items[] = $item;
        }

        return [
            'items' => $items,
        ];
    }

    public function getTransactionData()
    {
        $ecEnabled = $this->BConfig->get('modules/Sellvana_GoogleApi/ua_enable_ec');
        $orderId = $this->BSession->get('last_order_id');
        $order = $this->Sellvana_Sales_Model_Order->load($orderId);

        $trans = [
            'id' => $order->get('unique_id'),
            'affiliation' => $this->BConfig->get('modules/FCom_Core/company_name'),
            'revenue' => $order->get('grand_total'),
            'shipping' => $order->get('shipping_price'),
            'tax' => $order->get('tax_amount'),
            'coupon' => $order->get('coupon_code'),
        ];

        $items = [];
        foreach ($order->items() as $item) {
            $items[] = [
                'id' => $ecEnabled ? $item->get('product_sku') : $order->get('unique_id'),
                'name' => $item->get('product_name'),
                'sku' => $ecEnabled ? null : $item->get('product_sku'),
                // 'category' => null, //TODO: implement cart/order original product category tracking?
                // 'variant' => null, //TODO: implement variant data
                // 'coupon' => null, //TODO: implement item coupon
                'price' => $item->get('price'),
                'quantity' => $item->get('qty_ordered'),
            ];
        }

        return [
            'transaction' => $trans,
            'items' => $items,
        ];
    }

}