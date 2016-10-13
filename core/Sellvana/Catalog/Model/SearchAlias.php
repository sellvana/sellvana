<?php

class Sellvana_Catalog_Model_SearchAlias extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_search_alias';
    static protected $_origClass = __CLASS__;

    const TYPE_FULL = 'F', TYPE_WORD = 'W';

    protected static $_importExportProfile = ['skip' => ['id', 'create_at', 'update_at']];

    protected static $_fieldOptions = [
        'alias_type' => [
            self::TYPE_FULL => 'Full',
            self::TYPE_WORD => 'Word',
        ],
    ];


    public function fetchSearchAlias($query)
    {
        /*
        $sData =& $this->BSession->dataToUpdate();
        if (!empty($sData['search_alias'][$query])) {
            return $sData['search_alias'][$query];
        }
        */
        //TODO: implement 'W'ord aliases
        $data = ['alias_type' => 'F', 'alias_term' => (string)$query];
        $record = $this->loadWhere($data);
        if (!$record) {
            //$sData['search_alias'][$query] = $query;
            return false;
        }
        $record->add('num_hits')->save();
        //$sData['search_alias'][$query] = $record->get('target_term');
        return $record;
    }
}
