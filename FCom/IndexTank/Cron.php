<?php

class FCom_IndexTank_Cron extends BClass
{
    public static function bootstrap()
    {
        FCom_Cron::i()
            ->task('* * * * *', 'FCom_IndexTank_Cron.indexAll');

        BPubSub::i()
            ->on('FCom_IndexTank_Index_Product::add', 'FCom_IndexTank_Index_Product::onProductIndexAdd');
    }

    public function indexAll()
    {
        set_time_limit(0);

        $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->getIndexingStatus();
        if ($indexingStatus->status == 'pause') {
            return;
        }

        $this->indexAllNotIndexed();
    }

    protected function indexAllNotIndexed()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where_in("indextank_indexed", array(1,0));
        $batchSize = BConfig::i()->get('modules/FCom_IndexTank/index_products_limit');
        if (!$batchSize) {
            $batchSize = 500;
        }
        $offset = 0;
        $products = $orm->offset($offset)->limit($batchSize)->find_many();
        if (!$products) {
            return;
        }
        $productIds = array();
        foreach ($products as $p) {
            $productIds[] = $p->id();
        }
        //before index
        FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 1, "indextank_indexed_at" => date("Y-m-d H:i:s")),
                    "id in (".implode(",", $productIds).")");
        //index
        try {
            FCom_IndexTank_Index_Product::i()->add($products, $batchSize);
        } catch(Exception $e) {
            //do not update products index status because of exception
            return true;
        }
        //after index
        FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 2, "indextank_indexed_at" => date("Y-m-d H:i:s")),
                    "id in (".implode(",", $productIds).")");

        FCom_IndexTank_Model_IndexingStatus::i()->updateInfoStatus();
    }
}