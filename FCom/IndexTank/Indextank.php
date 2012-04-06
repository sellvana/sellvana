<?php

class FCom_IndexTank extends BClass
{
    /**
    * Indextank Service instance
    *
    * @var Indextank_API
    */
    protected $_indextank;

    protected $_products;

    static public function bootstrap()
    {
        BApp::m()->autoload('lib');
    }

    public function service()
    {
        if (empty($this->_indextank)) {
            $this->_indextank = new Indextank_Api($api_url);
        }
        return $this->_indextank;
    }

    public function products()
    {
        if (empty($this->_products)){
            try {
                $this->_products = $this->_indextank->create_index('products');
            } catch (Exception $e){
                $this->_products = $this->_indextank->get_index('products');
            }
        }
        return $this->_products;
    }

    public function add($products)
    {

    }
    public function update($products)
    {

    }
    public function update_categories($product_categories)
    {

    }
    public function update_variables($product_variables)
    {

    }
    public function delete($products)
    {

    }
}