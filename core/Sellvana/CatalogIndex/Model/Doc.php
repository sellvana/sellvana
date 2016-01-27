<?php

class Sellvana_CatalogIndex_Model_Doc extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_index_doc';

    public function flagReindex($productIds)
    {
        if (!$productIds) {
            return;
        }
        $this->update_many(['flag_reindex' => 1], ['id' => $this->BUtil->arrayCleanInt($productIds)]);
    }
}
