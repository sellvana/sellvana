<?php

/**
 * Class Sellvana_MultiVendor_Admin
 *
 * @property Sellvana_MultiVendor_Model_Vendor $Sellvana_MultiVendor_Model_Vendor
 * @property Sellvana_MultiVendor_Model_VendorProduct $Sellvana_MultiVendor_Model_VendorProduct
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */

class Sellvana_MultiVendor_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'multi_vendor'          => 'Multi Vendor',
            'settings/multi_vendor' => 'Multi Vendor Settings',
        ]);
    }

    public function onProductFormPostAfterValidate($args)
    {
        $vpData = $this->BRequest->post('vendor_product');
        if (!$vpData) {
            return;
        }
        $product = $args['model'];


        $hlp = $this->Sellvana_MultiVendor_Model_VendorProduct;
        $vp = $hlp->load($product->id(), 'product_id');
        if ($vp) {
            if (!empty($vpData['vendor_id'])) {
                $vp->set($vpData)->save();
            } else {
                $vp->delete();
            }
        } elseif (!empty($vpData['vendor_id'])) {
            $vpData['product_id'] = $product->id();
            $hlp->create($vpData)->save();
        }
    }
}
