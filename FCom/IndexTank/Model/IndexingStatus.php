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
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
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
}
