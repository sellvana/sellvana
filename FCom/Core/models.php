<?php

class FCom_ProductCategory extends BModel {
    protected static $_table = 'a_product_category';
}

class FCom_ProductCustom extends BModel
{
    static protected $_table = 'fcom_product_custom';
}

class FCom_Category extends BModel
{
    static protected $_table = 'fcom_category';
}

class FCom_CategoryProduct extends BModel
{
    static protected $_table = 'fcom_category_product';
}

class FCom_Customer extends BModel
{
    static protected $_table = 'fcom_customer';
}

class FCom_CustomerAddress extends BModel
{
    static protected $_table = 'fcom_customer_address';
}

class FCom_Cart extends BModel
{
    static protected $_table = 'fcom_cart';
}

class FCom_CartAddress extends BModel
{
    static protected $_table = 'fcom_cart_address';
}

class FCom_CartItem extends BModel
{
    static protected $_table = 'fcom_cart_item';
}

class FCom_Order extends BModel
{
    static protected $_table = 'fcom_order';
}

class FCom_OrderAddress extends BModel
{
    static protected $_table = 'fcom_order_address';
}

class FCom_OrderItem extends BModel
{
    static protected $_table = 'fcom_order_item';
}