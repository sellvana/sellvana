<?php

class FCom_Catalog_Model_ProductLink extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_link';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = array(
        'skip'    => array( 'id' ),
        'related' => array(
            'product_id'        => 'FCom_Catalog_Model_Product.id',
            'linked_product_id' => 'FCom_Catalog_Model_Product.id',
        )
    );

    public function productsByType($id, $type)
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')
            ->select('*');
        $orm->join('FCom_Catalog_Model_ProductLink', array('pl.linked_product_id','=','p.id'), 'pl')
                ->where('link_type', $type)
                ->where('pl.product_id', $id);
        return $orm->find_many();
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('create_at', BDb::now(), 'IFNULL');
        $this->set('update_at', BDb::now());

        // Add to the end?
        if (is_int($this->get('position')) === false) {

            $maxCurrentPosition = FCom_Catalog_Model_ProductLink::i()
                ->orm()
                ->select_expr('max(position) as max_pos')
                ->where('product_id', $this->get('product_id'))
                ->find_one();

            if (!$maxCurrentPosition) {
                $maxCurrentPosition = 1;
            } else {
                $maxCurrentPosition = $maxCurrentPosition->get('max_pos');
            }
            $maxCurrentPosition++;

            $this->set('position', $maxCurrentPosition);
        }

        return true;
    }
}