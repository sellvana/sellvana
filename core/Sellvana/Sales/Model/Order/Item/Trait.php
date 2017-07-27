<?php

/**
 * Trait Sellvana_Sales_Model_Order_Item_Trait
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property BApp BApp
 * @property BConfig BConfig
 * @property FCom_Core_Main FCom_Core_Main
 */
trait Sellvana_Sales_Model_Order_Item_Trait
{
    protected $_product;

    public function setProduct(Sellvana_Catalog_Model_Product $product)
    {
        $this->_product = $product;
        return $this;
    }

    /**
     * @return Sellvana_Catalog_Model_Product
     */
    public function product()
    {
        if (!$this->_product) {
            $this->_product = $this->Sellvana_Catalog_Model_Product->load($this->get('product_id'));
        }
        return $this->_product;
    }

    public function thumbUrl($w, $h = null, $full = false)
    {
        $product = $this->product();

        if ($product) {
            return $product->thumbUrl($w, $h, $full);
        }

        $default = $this->BConfig->get('modules/Sellvana_Catalog/default_image');
        if ($default) {
            if ($default[0] === '@') {
                $default = $this->BApp->src($default, 'baseSrc', false);
            }
        } else {
            $default = $this->BConfig->get('web/media_dir') . '/image-not-found.jpg';
        }
        return $this->FCom_Core_Main->resizeUrl($default, ['s' => $w . 'x' . $h, 'full_url' => $full]);
    }
}