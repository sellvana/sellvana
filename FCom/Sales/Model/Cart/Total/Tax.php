<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_Total_Tax extends FCom_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'tax';
    protected $_label = 'Tax';
    protected $_cartField = 'tax_amount';
    protected $_sortOrder = 80;

    /**
     * @return FCom_Sales_Model_Cart_Total_Tax
     */
    public function calculate()
    {
        $result = [];

        $this->BEvents->fire(__METHOD__, ['cart' => $this->_cart, 'result' => &$result]);

        /*
        $this->_cart->setData('tax_details', $result);

        foreach ($this->_cart->items() as $item) {
            if (!empty($result['items'][$item->id()])) {
                $r = $result['items'][$item->id()];
                $item->set([
                    'row_tax' => $r['row_tax'],
                ]);
            }
        }
        */

        $this->_value = $this->_cart->get('tax_amount'); //$result['tax_amount'];
        //$this->_cart->set('tax_amount', $this->_value);
        //$this->_cart->add('grand_total', $this->_value);

        return $this;
    }
}