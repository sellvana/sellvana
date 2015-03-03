<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Model_Cart_Total_Subtotal
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_MultiSite_Main $Sellvana_MultiSite_Main
 * @property Sellvana_MultiCurrency_Main $Sellvana_MultiCurrency_Main
 */
class Sellvana_Sales_Model_Cart_Total_Subtotal extends Sellvana_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'subtotal';
    protected $_label = 'Subtotal';
    protected $_cartField = 'subtotal';
    protected $_sortOrder = 10;

    /**
     * @return Sellvana_Sales_Model_Cart_Total_Subtotal
     */
    public function calculate()
    {
        $itemNum = 0;
        $itemQty = 0;
        $subtotal = 0;
        foreach ($this->_cart->items() as $item) {
            /*
            // TODO: figure out handling cart items of products removed from catalog
            if (!$item->getProduct()) {
                $this->_cart->removeProduct($item->product_id);
            }
            */
            $product = $item->getProduct();
            $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
            $customerGroup = $customer->getCustomerGroupId();
            // todo , load site currency, load site id
            $site = null;
            $currency = null;
            if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')) {
                $site = $this->Sellvana_MultiSite_Main->getCurrentSiteData();
            }
            if ($this->BModuleRegistry->isLoaded('Sellvana_MultiCurrency')) {
                $currency = $this->Sellvana_MultiCurrency_Main->getCurrentCurrency();
            }
            $tierPrice = $product->getTierPrice($item->getQty(), $customerGroup, $site['id'], $currency);
            if($tierPrice){
                $item->set('tier_price', $tierPrice);
            }
            $itemNum++;
            $itemQty += $item->get('qty');
            $rowTotal = $item->calcRowTotal();
            $subtotal += $rowTotal;
            $item->set('row_total', $rowTotal);
        }

        $this->_value = $subtotal;
        $this->_cart->set([
            'item_num' => $itemNum,
            'item_qty' => $itemQty,
            'subtotal' => $subtotal,
        ]);

        $this->_cart->getTotalByType('grand_total')->resetComponents()->addComponent($this->_value, 'subtotal');

        return $this;
    }
}
