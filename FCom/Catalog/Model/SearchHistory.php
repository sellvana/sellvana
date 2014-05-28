<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Catalog_Model_SearchHistory extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_search_history';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = ['skip' => ['id'], 'unique_key' => ['term_type', 'query']];
    static public function addSearchHit($query, $numProductsFound = null)
    {
        if ($query === '' || is_null($query)) {
            return false;
        }

        $sData =& BSession::i()->dataToUpdate();
        if (!empty($sData['search_history'][$query])) {
            return null;
        }
        $sData['search_history'][$query] = $query;
        //TODO: add 'W'ord functionality
        $data = ['term_type' => 'F', 'query' => (string)$query];
        $record = static::loadWhere($data);
        if ($record) {
            $record->add('num_searches');
        } else {
            $record = static::create($data);
            $record->set(['num_searches' => 1, 'first_at' => BDb::now()]);
        }
        if (!is_null($numProductsFound)) {
            $record->set('num_products_found_last', $numProductsFound);
        }
        $record->set('last_at', BDb::now())->save();
        return $record;
    }
}
