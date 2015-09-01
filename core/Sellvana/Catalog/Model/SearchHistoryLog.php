<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Catalog_Model_SearchHistoryLog extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_search_history_log';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = ['skip' => ['id'], 'unique_key' => ['term_type', 'query']];

    /**
     * @param int $queryId
     * @return $this|bool
     */
    public function addSearchHit($queryId)
    {
        $queryId = (int)$queryId;
        if ($queryId) {
            return $this->create([
                'query_id' => $queryId,
                'create_at' => $this->BDb->now()
            ])
                ->save();
        }
        return false;
    }
}
