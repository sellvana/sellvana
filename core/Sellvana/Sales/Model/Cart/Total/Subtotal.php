<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Model_Cart_Total_Subtotal
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_MultiSite_Main $Sellvana_MultiSite_Main
 * @property Sellvana_MultiCurrency_Main $Sellvana_MultiCurrency_Main
 * @property Sellvana_CustomerGroups_Model_Group $Sellvana_CustomerGroups_Model_Group
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
            if ($customer) {
                $customerGroup = $customer->getCustomerGroupId();
            } else {
                $customerGroup = $this->Sellvana_CustomerGroups_Model_Group->notLoggedInId();
            }
            // todo , load site currency, load site id
            $site = null;
            $currency = null;
            if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')) {
                $site = $this->Sellvana_MultiSite_Main->getCurrentSiteData();
            }
            if ($this->BModuleRegistry->isLoaded('Sellvana_MultiCurrency')) {
                $currency = $this->Sellvana_MultiCurrency_Main->getCurrentCurrency();
            }
            $productPrices = $product->getAllPrices($item->getQty(), $customerGroup, $site['id'], $currency, $this->BDb->now());
            //$itemPrice = $item->get('price');
            if($item->get('custom_price')){
                $itemPrice = $item->get('custom_price');
            } else {
                $itemPrice = $product->getCatalogPrice();
                $tierPrice = $product->getTierPrice($item->getQty());
                if ($tierPrice) {
                    $itemPrice = min($itemPrice, $tierPrice);
                }
            }

            if ($item->get('variant')) {
                // the function can be add %, add $, set %, set $
                $itemPrice = $product->variantPrice($itemPrice, $item->get('variant'));
            }

            if ($item->get('shopper_fields')) {
                foreach ($item->get('shopper_fields') as $f => $fData) {
                    $itemPrice = $this->priceFunction($itemPrice, $fData);
                }
            }

            $item->set('price', $itemPrice);

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

    /**
     * @param float $itemPrice - item price to the moment
     * @param array $fData - an array with shopper field config
     * @return float
     */
    public function priceFunction($itemPrice, $fData)
    {
        if(!is_array($fData) || empty($fData['operation']) || empty($fData['price'])) {
            return $itemPrice;
        }
        $price = $fData['price'];
        switch ($fData['operation']) {
            case '+$':
                // add fixed amount to price
                $itemPrice += (float) $price;
                break;
            case '+%':
                // add percent of the price to price
                $itemPrice += $itemPrice * ($price / 100);
                break;
            case '-$':
                // subtract fixed amount
                $itemPrice -= (float) $price;
                break;
            case '-%':
                // subtract a fraction of the price
                $itemPrice -= $itemPrice / ($price / 100);
                break;
            case '$$':
                // set the price to provided amount
                $itemPrice = $price;
                break;
        }

        return $itemPrice;
    }
}

/*
 $item; // cart item
$prod = $item.product();

$prices = $prod.prices(); // specific for current environment

if ($item.custom_price) {

  $itemPrice = $item.custom_price;

} else {

  $itemPrice = $prices['base'] ? $prices['base'] : $prod.base_price;

  if ($prices['sale'] && checkSaleDates($prices['sale'])) {
    $itemPrice = min($itemPrice, $prices['sale']);
  } elseif ($prod.sale_price && checkSaleDates($prod)) {
    $itemPrice = min($itemPrice, $prod.sale_price);
  }

  foreach ($prices['tier'] as $tQty => $tPrice) {
    if ($item.qty >= $tQty) {
      $itemPrice = min($itemPrice, $tPrice);
    }
  }

  if ($prices['promo']) {
    $itemPrice = min($itemPrice, $prices['promo'];
  }
}

if ($item.variant) {
  // the function can be add %, add $, set %, set $
  $itemPrice = variantPriceFunction($itemPrice, $item.variant);
}

foreach ($item.shopper_fields as $f => $fData) {
  $itemPrice = priceFunction($itemPrice, $fData);
}
 */
