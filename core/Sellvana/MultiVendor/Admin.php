<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiVendor_Admin
 *
 * @property Sellvana_MultiVendor_Model_Vendor $Sellvana_MultiVendor_Model_Vendor
 * @property Sellvana_MultiVendor_Model_VendorProduct $Sellvana_MultiVendor_Model_VendorProduct
 */

class Sellvana_MultiVendor_Admin extends BClass
{
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
            $vp['product_id'] = $product->id();
            $hlp->create($vp)->save();
        }
    }
}