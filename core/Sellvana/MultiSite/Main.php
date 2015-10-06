<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Main
 *
 * @property Sellvana_MultiSite_Model_Site $Sellvana_MultiSite_Model_Site
 * @property Sellvana_MultiSite_Frontend $Sellvana_MultiSite_Frontend
 * @property Sellvana_CatalogFields_Model_ProductFieldData $Sellvana_CatalogFields_Model_ProductFieldData
 * @property Sellvana_CatalogFields_Model_FieldOption $Sellvana_CatalogFields_Model_FieldOption
 */
class Sellvana_MultiSite_Main extends BClass
{
    public function isFieldDataBelongsToThisSite($row)
    {
        $siteId = $this->Sellvana_MultiSite_Frontend->getCurrentSite();
        return ($row->get('site_id') == $siteId || is_null($row->get('site_id')));
    }

    /**
     * @param $oldField
     * @param $field
     * @return bool
     */
    public function shouldCombineFieldDataValues($oldField, $field)
    {
        $data = json_decode($field['serialized']);
        $oldData = json_decode($oldField['serialized']);
        return ($oldField['field_code'] == $field['field_code'] && $data->site_id == $oldData->site_id);
    }
}
