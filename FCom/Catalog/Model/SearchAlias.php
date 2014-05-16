<?php

class FCom_Catalog_Model_SearchAlias extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_search_alias';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = ['skip' => ['id', 'create_at', 'update_at'],];

    static public function processSearchQuery($query)
    {
        $sData =& BSession::i()->dataToUpdate();
        if (!empty($sData['search_alias'][$query])) {
            return $sData['search_alias'][$query];
        }
        //TODO: implement 'W'ord aliases
        $data = ['alias_type' => 'F', 'alias_term' => $query];
        $record = static::load($data);
        if (!$record) {
            $sData['search_alias'][$query] = $query;
            return $query;
        }
        $record->add('num_hits')->save();
        $sData['search_alias'][$query] = $record->get('target_term');
        return $record->get('target_term');
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('create_at', BDb::now(), 'IFNULL');
        $this->set('update_at', BDb::now());

        return true;
    }
}
