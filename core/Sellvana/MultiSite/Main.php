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

    public function saveProductsFieldSiteData($products)
    {
        $fields = $this->Sellvana_CatalogFields_Model_Field->getAllFields('id');

        $pIds = $this->BUtil->arrayToOptions($products, '.id');
        if (!$pIds) {
            return $this;
        }
        /** @var Sellvana_CatalogFields_Model_ProductFieldData[][][][] $fieldsData */
        $rawFieldsData = $this->Sellvana_CatalogFields_Model_ProductFieldData->orm('pf')
            ->where_in('product_id', $pIds)
            ->find_many();
        $fieldsData = [];
        foreach ($rawFieldsData as $rawData) {
            $siteId = $rawData->get('site_id') ?: 'default';
            if (empty($fieldsData[$rawData->get('product_id')])) {
                $fieldsData[$rawData->get('product_id')] = [];
            }

            if (empty($fieldsData[$rawData->get('product_id')][$siteId])) {
                $fieldsData[$rawData->get('product_id')][$siteId] = [];
            }

            if (empty($fieldsData[$rawData->get('product_id')][$siteId][$rawData->get('field_id')])) {
                $fieldsData[$rawData->get('product_id')][$siteId][$rawData->get('field_id')] = [];
            }

            array_push($fieldsData[$rawData->get('product_id')][$siteId][$rawData->get('field_id')], $rawData);
        }

        $options = $this->Sellvana_CatalogFields_Model_FieldOption->preloadAllFieldsOptions()->getAllFieldsOptions();
        $optionsByLabel = [];
        foreach ($options as $fieldId => $fieldOptions) {
            foreach ($fieldOptions as $optionId => $option) {
                $optionsByLabel[$fieldId][strtolower($option->get('label'))] = $option->id();
            }
        }
        foreach ($products as $product) { // go over products
#echo "<pre>"; debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); echo "</pre>"; var_dump($product->as_array());
            $pId = $product->id();
            $pData = $product->as_array();
            $siteValues = $product->get('multisite_fields');
            if ($siteValues === null) {
                continue;
            }

            foreach ($siteValues as $siteId => $siteValue) {
                foreach ($siteValue as $fieldId => $value) { // go over all product fields data
                    if (empty($fields[$fieldId])) {
                        continue;
                    }

                    $field = $fields[$fieldId];
                    $fId = $field->id();
                    $fieldType = $field->get('table_field_type');
                    $fieldCode = $field->get('field_code');
                    $tableColumn = $this->Sellvana_CatalogFields_Model_ProductFieldData->getTableColumn($fieldType);

                    if ($fieldType === 'options') {
                        $value = explode(',', $value);
                    } elseif (!is_array($value)) {
                        $value = [$value];
                    }

                    foreach ($value as $singleValue) {
                        if (!empty($fieldsData[$pId][$siteId][$fId])) { // if this field data record already exists
                            $fData = array_shift($fieldsData[$pId][$siteId][$fId]);
                            if (!empty($pData['_custom_fields_remove']) && in_array($fId, $pData['_custom_fields_remove'])) {
                                $fData->delete();
                                $product->set($fieldCode, null);
                                continue;
                            }
                        } else { // if this is a new entry
                            $fData = $this->Sellvana_CatalogFields_Model_ProductFieldData->create([
                                'product_id' => $pId,
                                'field_id' => $fId,
                                'site_id' => $siteId,
                                'set_id' => (!empty($fieldsData[$pId]['default'][$fId][0])) ? $fieldsData[$pId]['default'][$fId][0]->get('set_id') : null
                            ]);
                        }
                        if ($fieldType === 'options') {
                            $valueLower = strtolower($singleValue);
                            if (!empty($optionsByLabel[$fId][$valueLower])) { // option exists?
                                $singleValue = $optionsByLabel[$fId][$valueLower];
                            } else {                                   // option doesn't exist
                                if ($this->Sellvana_CatalogFields_Model_ProductFieldData->getAutoCreateOptions()) { // allow option auto-creation?
                                    $optionId = $this->Sellvana_CatalogFields_Model_FieldOption->create([
                                        'field_id' => $fId,
                                        'label' => $singleValue,
                                    ])->save()->id();
                                    $singleValue = $optionId;
                                    $optionsByLabel[$fId][$valueLower] = $optionId;
                                } else { // don't auto-create
                                    $singleValue = null;
                                }
                            }
                        }
                        $fData->set($tableColumn, $singleValue);
                        $fData->save();
                    }
                }
            }

            // cleaning up deleted values
            foreach ($fieldsData as $prodData) {
                foreach ($prodData as $siteId => $siteData) {
                    if ($siteId == 'default') {
                        continue;
                    }
                    foreach ($siteData as $fieldData) {
                        foreach ($fieldData as $valueData) {
                            $valueData->delete();
                        }
                    }
                }
            }
        }

        return $this->Sellvana_CatalogFields_Model_ProductFieldData;
    }

}
