<?php

class FCom_IndexTank_Model_Product extends FCom_IndexTank
{
    protected $_index_name = 'products';
    protected $_model;

    const LATITUDE = 0;
    const LONGTITUDE = 1;
    const PRICE = 2;
    const RATING = 3;

    /**
     *
     * @return Indextank_Index
     */
    public function model()
    {
        if (empty($this->_model)){
            try {
                $this->_model = $this->service()->create_index($this->_index_name);
            } catch (Exception $e){
                $this->_model = $this->service()->get_index($this->_index_name);
            }
        }
        return $this->_model;
    }

    public function search($query)
    {
        $queryString = "product_name:($query)^10 description:($query) ";
        try {
            $result = $this->model()->search($queryString);
        } catch(Exception $e) {
            throw $e;
        }

        if (!$result->matches){
            return false;
        }

        $products = array();
        $product_model = new stdClass;
        foreach ($result->results as $res){
            $products[] = $product_model->get($res->docid);
        }
        return $products;
    }

    public function add($products)
    {
        if (!is_array($products))
        {
            $products = array($products);
        }
        /**
         * Structure of documents array
         $documents = array();
         $documents[]= array(
         *      "docid" => "doc1",
         *      "fields" => array( "text" => "text1 is short" ) );
         $documents[]= array(
         *      "docid" => "doc2",
         *      "fields" => array( "text" => "text2 is a bit longer" ),
         *      "categories" => array("priceRange" => "$100 to $200", "Brand" => "Sony") );
         $documents[]= array(
         *      "docid" => "doc3",
         *      "fields" => array( "text" => "text3 is longer than text2" ),
         *      "variables" => array( 0 => 1.5 )
        */
        $documents = array();

        foreach($products as $product){
            //get all categories
            $categories = array(
                'priceRange' => $product->getPriceRangeText(),
                'Brand' => $product->getBrandName()
                    );
            //get all variables
            $variables = array(
                self::LATITUDE => $product->latitude,
                self::LONGTITUDE => $product->longtitude,
                self::PRICE => $product->price,
                self::RATING => $product->rating_avg
                    );

            //get all text fields
            $fields = array(
                "text" => $product->description,
                "product_name" => $product->product_name
                    );

            $documents[]['docid'] = $product->id();
            $documents[]['fields'] = $fields;
            if (!empty($categories)){
                $documents[]['categories'] = $categories;
            }
            if (!empty($variables)){
                $documents[]['variables'] = $variables;
            }
        }

        $this->model()->add_documents($documents);
    }

    public function update_categories($product_id)
    {
        //get dirty categories by product_id
        $product_categories = array();
        $this->model()->update_categories($product_id, $product_categories);
    }

    public function update_variables($product_id)
    {
        //get dirty variables by product_id
        $product_variables = array();
        $this->model()->update_variables($product_id, $product_variables);
    }

    public function delete($products)
    {
        if (!is_array($products)){
            $products = array($products);
        }
        $docids = array();
        foreach($products as $product){
            $docids[] = $product->id();
        }
        $this->model()->delete_documents($docids);
    }
}