<?php

class Fcom_IndexTank_Cron_Index extends BClass
{
    static public function index_all()
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
        $total_records = $orm->count();
        $batch_size = 500;
        $offset = 0;
        $counter = 0;
        $products = $orm->offset($offset)->limit($batch_size)->find_many();
        while($products) {
            $counter += count($products);
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

            $this->update_info_status("index_all_crashed", $counter);

            $offset += $batch_size;
            //get new batch of data
            $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 1);
            $products = $orm->offset($offset)->limit($batch_size)->find_many();
            unset($orm);
        }
    }

    protected function index_all_not_indexed()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 0);
        $batch_size = 500;
        $offset = 0;
        $counter = 0;
        $products = $orm->offset($offset)->limit($batch_size)->find_many();
        while($products) {
            $counter += count($products);
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

            $this->update_info_status("index_all_new", $counter);

            $offset += $batch_size;
            //get new batch of data
            $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 0);
            $products = $orm->offset($offset)->limit($batch_size)->find_many();
            unset($orm);
        }
    }

    protected function update_info_status($task, $counter)
    {
        $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->orm()->where("task", $task);
        if (!$indexingStatus){
            $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->orm()->create();
            $indexingStatus->task = $task;
        }
        $percent = round(($counter/$total_records)*100, 2);
        $indexingStatus->info = "Indexed {$percent}% documents";
        $indexingStatus->updated_at = date("Y-m-d H:i:s");
        $indexingStatus->save();
    }
}