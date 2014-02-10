<?php

class FCom_IndexTank_Model_IndexingStatus extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_indexing_status';

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Model_IndexingStatus
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public function getIndexingStatus($task = 'index_all_new')
    {
        $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->orm()->where("task", $task)->find_one();
        if (!$indexingStatus) {
            $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->orm()->create();
            $indexingStatus->task = $task;
            $indexingStatus->status = 'start';
            $indexingStatus->updated_at = date("Y-m-d H:i:s");
            $indexingStatus->save();
        }
        return $indexingStatus;
    }

    public function setIndexingStatus($status, $task = 'index_all_new')
    {
        $indexingStatus = $this->getIndexingStatus($task);
        $indexingStatus->status = $status;
        $indexingStatus->save();
        return $indexingStatus;
    }

    public function updateInfoStatus()
    {
        $countNotIndexed = FCom_Catalog_Model_Product::orm()
                ->where('indextank_indexed', 0)
                ->count();
        $countTotal = FCom_Catalog_Model_Product::orm()->count();
        $percent =  (($countTotal - $countNotIndexed)/$countTotal)*100;
        $indexed = $countTotal - $countNotIndexed;

        $status = FCom_IndexTank_Index_Product::i()->status();
        $indexSize = $status['size'];

        $indexingStatus = $this->getIndexingStatus();
        $indexingStatus->status = 'start';
        $indexingStatus->percent = ceil($percent);
        $indexingStatus->to_index = $countNotIndexed;
        $indexingStatus->index_size = $indexSize;
        $indexingStatus->indexed = $indexed;
        $indexingStatus->info = "{$countNotIndexed} documents left";
        $indexingStatus->updated_at = date("Y-m-d H:i:s");
        $indexingStatus->save();
    }
}
