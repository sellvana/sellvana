<?php

/**
 * Class Sellvana_Sales_Model_Order_Shipment_Package
 *
 * @property Sellvana_Sales_Model_Order_Shipment $Sellvana_Sales_Model_Order_Shipment
 * @property Sellvana_Sales_Model_Order_Shipment_Item $Sellvana_Sales_Model_Order_Shipment_Item
 * @property Sellvana_Sales_Model_Order_Shipment_Package_State $Sellvana_Sales_Model_Order_Shipment_Package_State
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_Sales_Model_Order_Shipment_Package extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_shipment_package';
    protected static $_origClass = __CLASS__;

    /**
     * @var Sellvana_Sales_Model_Order_Shipment_Item[]
     */
    protected $_items;

    /**
     * @var Sellvana_Sales_Model_Order_Shipment_State
     */
    protected $_state;

    /**
     * @var Sellvana_Sales_Model_Order_Shipment
     */
    protected $_shipment;

    /**
     * @var Sellvana_Sales_Method_Shipping_Abstract
     */
    protected $_shippingMethod;

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_Item[]
     */
    public function items()
    {
        if (null === $this->_items) {
            $this->_items = $this->Sellvana_Sales_Model_Order_Shipment_Item->orm('osi')
                ->where('package_id', $this->id())
                ->join('Sellvana_Sales_Model_Order_Item', ['oi.id', '=', 'osi.order_item_id'], 'oi')
                ->select('osi.*')->select(['oi.product_id', 'product_sku', 'inventory_id', 'inventory_sku',
                    'product_name', 'shipping_size', 'shipping_weight'])
                ->find_many();
        }
        return $this->_items;
    }

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_Package_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Order_Shipment_Package_State->factory($this);
        }
        return $this->_state;
    }

    /**
     * @return bool|Sellvana_Sales_Method_Shipping_Abstract
     * @throws BException
     */
    public function shippingMethod()
    {
        if (!$this->_shippingMethod) {
            if (!$this->_shipment) {
                $this->_shipment = $this->Sellvana_Sales_Model_Order_Shipment->load($this->get('shipment_id'));
            }

            $method = $this->_shipment->get('carrier_code');
            $methodClass = $this->Sellvana_Sales_Main->getShippingMethodClassName($method);
            if (!$methodClass) {
                return false;
            }

            $this->_shippingMethod = $this->$methodClass;
        }

        return $this->_shippingMethod;
    }

    public function label()
    {
        if (!($shippingMethod = $this->shippingMethod())) {
            return false;
        }

        return $shippingMethod->getPackageLabel($this);
    }

    public function canTrackingUpdate()
    {
        if (!($shippingMethod = $this->shippingMethod())) {
            return false;
        }

        return $shippingMethod->canTrackingUpdate();
    }

    public function getTrackingUrl()
    {
        if (!($shippingMethod = $this->shippingMethod())) {
            return false;
        }

        return $shippingMethod->getTrackingUrl($this);
    }

    /**
     * @param $fileName
     * @param $content
     * @throws BException
     */
    public function putFile($fileName, $content)
    {
        $path = $this->getStoragePath() . '/' . $fileName;
        if (!@file_put_contents($path, $content)){
            throw new BException('Can\'t write file to package storage.');
        }
    }

    /**
     * @param $fileName
     * @return string
     * @throws BException
     */
    public function getFilePath($fileName)
    {
        $path = $this->getStoragePath() . '/' . $fileName;

        if (!is_file($path)){
            throw new BException('Requested file doesn\'t exist.' . $path);
        }

        return $path;
    }

    /**
     * @param $fileName
     * @return string
     * @throws BException
     */
    public function getFileContent($fileName)
    {
        $path = $this->getFilePath($fileName);

        return @file_get_contents($path);
    }

    /**
     * @return string
     * @throws BException
     */
    public function getStoragePath()
    {
        if (null === $this->get('id')) {
            throw new BException('Can\'t get package id.');
        }

        $randomPath = $this->BApp->storageRandomDir();
        $path = $randomPath . '/order/shipment/' . $this->get('shipment_id') . '/' . $this->get('id');

        $this->BUtil->ensureDir($path);

        return $path;
    }
}