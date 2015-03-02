<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_IndexTank_Model_IndexingStatus
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_IndexTank_Index_Product $Sellvana_IndexTank_Index_Product
 * @property Sellvana_IndexTank_Model_IndexingStatus $Sellvana_IndexTank_Model_IndexingStatus
 */

class Sellvana_IndexTank_Model_IndexingStatus extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_indexing_status';

    public function getIndexingStatus($task = 'index_all_new')
    {
        $indexingStatus = $this->Sellvana_IndexTank_Model_IndexingStatus->orm()->where("task", $task)->find_one();
        if (!$indexingStatus) {
            $indexingStatus = $this->Sellvana_IndexTank_Model_IndexingStatus->create();
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
        $countNotIndexed = $this->Sellvana_Catalog_Model_Product->orm()
                ->where('indextank_indexed', 0)
                ->count();
        $countTotal = $this->Sellvana_Catalog_Model_Product->orm()->count();
        $percent =  (($countTotal - $countNotIndexed) / $countTotal) * 100;
        $indexed = $countTotal - $countNotIndexed;

        $status = $this->Sellvana_IndexTank_Index_Product->status();
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
