<?php

class Sellvana_Catalog_Model_SearchHistory extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_search_history';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = ['skip' => ['id'], 'unique_key' => ['term_type', 'query']];
    public function addSearchHit($query, $numProductsFound = null)
    {
        if ($query === '' || is_null($query)) {
            return false;
        }

        $sData =& $this->BSession->dataToUpdate();
        if ($this->BSession->get("search_history/{$query}")) {
            return null;
        }
        $this->BSession->set("search_history/{$query}", $query);
        //TODO: add 'W'ord functionality
        $data = ['term_type' => 'F', 'query' => (string)$query];
        $record = $this->loadWhere($data);
        if ($record) {
            $record->add('num_searches');
        } else {
            $record = $this->create($data);
            $record->set(['num_searches' => 1, 'first_at' => $this->BDb->now()]);
        }
        if (!is_null($numProductsFound)) {
            $record->set('num_products_found_last', $numProductsFound);
        }
        $record->set('last_at', $this->BDb->now())->save();
        return $record;
    }
}
