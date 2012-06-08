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
        //first finish not finsihed
        $this->indexAllInIndexing();
        //then finit not indexed
        $this->indexAllNotIndexed();
    }

    protected function indexAllInIndexing()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 1);
        $batchSize = 500;
        $offset = 0;
        $products = $orm->offset($offset)->limit($batchSize)->find_many();
        if(!$products){
            return;
        }
        $productIds = array();
        foreach($products as $p){
            $productIds[] = $p->id();
        }
        //before indexing
        //indexing
        FCom_IndexTank_Index_Product::i()->add($products, $batchSize);
        //after indexing
        FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 2, "indextank_indexed_at" => date("Y-m-d H:i:s")),
                    "id in (".implode(",", $productIds).")");

        $total_records = FCom_Catalog_Model_Product::i()->orm('p')->where("indextank_indexed", 1)->count();
        $this->updateInfoStatus("index_all_crashed", $total_records);
    }

    protected function indexAllNotIndexed()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 0);
        $batchSize = 500;
        $offset = 0;
        $products = $orm->offset($offset)->limit($batchSize)->find_many();
        if(!$products){
            return;
        }
        $productIds = array();
        foreach($products as $p){
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
        $this->updateInfoStatus("index_all_new", $total_records);
    }

    protected function updateInfoStatus($task, $total)
    {
        $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->orm()->where("task", $task)->find_one();
        if (!$indexingStatus){
            $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->orm()->create();
            $indexingStatus->task = $task;
        }
        $indexingStatus->info = "{$total} documents left";
        $indexingStatus->updated_at = date("Y-m-d H:i:s");
        $indexingStatus->save();
    }
}