<?php

class FCom_IndexTank_Cron extends BClass
{
    public static function bootstrap()
    {
        FCom_Cron::i()
            ->task('* * * * *', 'FCom_IndexTank_Cron.index_all');
    }

    public function index_all()
    {
        set_time_limit(0);
        //first finish not finsihed
        $this->index_all_in_indexing();
        //then finit not indexed
        $this->index_all_not_indexed();
    }

    protected function index_all_in_indexing()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 1);
        $batch_size = 2000;
        $offset = 0;
        $products = $orm->offset($offset)->limit($batch_size)->find_many();
        if(!$products){
            return;
        }
        $product_ids = array();
        foreach($products as $p){
            $product_ids[] = $p->id();
        }
        //before indexing
        //indexing
        FCom_IndexTank_Index_Product::i()->add($products, $batch_size);
        //after indexing
        FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 2, "indextank_indexed_at" => date("Y-m-d H:i:s")),
                    "id in (".implode(",", $product_ids).")");

        $total_records = FCom_Catalog_Model_Product::i()->orm('p')->where("indextank_indexed", 1)->count();
        $this->update_info_status("index_all_crashed", $total_records);
    }

    protected function index_all_not_indexed()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 0);
        $batch_size = 2000;
        $offset = 0;
        $products = $orm->offset($offset)->limit($batch_size)->find_many();
        if(!$products){
            return;
        }
        $product_ids = array();
        foreach($products as $p){
            $product_ids[] = $p->id();
        }
        //before index
        FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 1, "indextank_indexed_at" => date("Y-m-d H:i:s")),
                    "id in (".implode(",", $product_ids).")");
        //index
        FCom_IndexTank_Index_Product::i()->add($products, $batch_size);
        //after index
        FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 2, "indextank_indexed_at" => date("Y-m-d H:i:s")),
                    "id in (".implode(",", $product_ids).")");

        $total_records = FCom_Catalog_Model_Product::i()->orm('p')->where("indextank_indexed", 0)->count();
        $this->update_info_status("index_all_new", $total_records);
    }

    protected function update_info_status($task, $total)
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