<?php

class FCom_IndexTank_Cron extends BClass
{
    public static function bootstrap()
    {
        FCom_Cron::i()
            ->task('* * * * *', 'FCom_IndexTank_Cron.indexAll');
    }

    public function indexAll()
    {
        set_time_limit(0);

        $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->getIndexingStatus();
        if ($indexingStatus->status == 'stop' || $indexingStatus->status == 'pause') {
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
            FCom_IndexTank_Model_IndexingStatus::i()->setIndexingStatus('stop');

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
        FCom_IndexTank_Index_Product::i()->add($products, $batchSize);
        //after index
        FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 2, "indextank_indexed_at" => date("Y-m-d H:i:s")),
                    "id in (".implode(",", $productIds).")");

        $total_records = FCom_Catalog_Model_Product::i()->orm('p')->where("indextank_indexed", 0)->count();
        $this->updateInfoStatus($total_records);
    }

    protected function updateInfoStatus($total)
    {
        $countNotIndexed = FCom_Catalog_Model_Product::orm()->where('indextank_indexed', 0)->count();
        $countTotal = FCom_Catalog_Model_Product::orm()->count();
        $percent =  (($countTotal - $countNotIndexed)/$countTotal)*100;
        $indexed= $countTotal - $countNotIndexed;

        $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->getIndexingStatus();
        $indexingStatus->status = 'start';
        $indexingStatus->percent = ceil($percent);
        $indexingStatus->indexed = $indexed;
        $indexingStatus->info = "{$total} documents left";
        $indexingStatus->updated_at = date("Y-m-d H:i:s");
        $indexingStatus->save();
    }
}