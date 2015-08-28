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
        $storeCurrencySubtotal = 0;
        $baseCurrency = $this->BConfig->get('modules/FCom_Core/base_currency');
        $storeCurrency = $this->_cart->get('store_currency_code');
        if (!$storeCurrency) {
            $storeCurrency = $this->_cart->setStoreCurrency()->get('store_currency_code');
        }
        $currencyRate = 1;
        if ($storeCurrency != $baseCurrency) {
            $currencyRate = $this->Sellvana_MultiCurrency_Main->getRate($storeCurrency, $baseCurrency);
            if (!$currencyRate) {
                $currencyRate = 1;
            }
        }

        foreach ($this->_cart->items() as $item) {
            /*
            // TODO: figure out handling cart items of products removed from catalog
            if (!$item->getProduct()) {
                $this->_cart->removeProduct($item->product_id);
            }
            */
            $product = $item->getProduct();
            if ($item->get('custom_price')) {
                $itemPrice = $item->get('custom_price');
            } elseif ($product) {
                $itemPrice = $product->getCatalogPrice();
                $tierPrice = $product->getTierPrice($item->getQty());
                if ($tierPrice) {
                    $itemPrice = min($itemPrice, $tierPrice);
                }
            } else {
                $itemPrice = 0;
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

            $storeCurrencyPrice = $itemPrice;
            $itemPrice = $storeCurrencyPrice / $currencyRate;

            $itemNum++;
            $itemQty += $item->get('qty');

            $item->set('price', $itemPrice)->setData('store_currency/price', $storeCurrencyPrice);

            $rowTotal = $item->calcRowTotal();
            $subtotal += $rowTotal;

            if ($storeCurrency != $baseCurrency) {
                $storeCurrencySubtotal = $item->calcRowTotal(true);
            } else {
                $storeCurrencySubtotal = $rowTotal;
            }

            $item->set('row_total', $rowTotal)->setData('store_currency/row_total', $storeCurrencySubtotal);
        }

        $this->_value = $subtotal;
        $this->_storeCurrencyValue = $storeCurrencySubtotal;

        $this->_cart->set([
            'item_num' => $itemNum,
            'item_qty' => $itemQty,
            'subtotal' => $subtotal,
        ])->setData([
            'store_currency/subtotal' => $storeCurrencySubtotal,
        ]);

        /** @var Sellvana_Sales_Model_Cart_Total_GrandTotal $grandTotalModel */
        $grandTotalModel = $this->_cart->getTotalByType('grand_total');
        $grandTotalModel->resetComponents()->addComponent('subtotal', $this->_value, $this->_storeCurrencyValue);

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
