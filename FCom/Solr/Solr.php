<?php

class FCom_Solr extends BClass
{
    /**
    * Solr Service instance
    *
    * @var Apache_Solr_Service
    */
    protected $_solr;

    static public function bootstrap()
    {
        BApp::m()->autoload('lib');
    }

    public function service()
    {
        if (empty($this->_solr)) {
            $this->_solr = Apache_Solr_Service();
        }
        return $this->_solr;
    }

    public function add($products)
    {
        if (!is_array($products)) {
            $products = array($products);
        }
        $pIds = array();
        foreach ($products as $p) {
            $pIds[] = $p->id;
        }
        $categories = FCom_Catalog_Model_ProductCategory::i()->orm()->table_alias('pc')
            ->join(FCom_Catalog_Model_Category::table(), array('c.id','=','pc.category_id'), 'c')
            ->select('pc.product_id')->select('c.full_name')
            ->where_in('pc.product_id', $pIds)
            ->find_many();
        $allCats = array();
        foreach ($categories as $c) {
            $allCats[$c->product_id] = explode('|', $c->full_name);
        }
        $docs = array();
        foreach ($products as $p) {
            $doc = new Apache_Solr_Document();
            /*
            foreach ($p->as_array() as $k=>$v) {
                $doc->$k = $v;
            }
            */
            $doc->id = $p->id;
            $doc->product_name = $p->product_name;
            ////$doc->cat = $categories

            $docs[] = $doc;
        }
        $this->service()->addDocuments($docs);
    }
}
