<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_Catalog_Model_Category Sellvana_Catalog_Model_Category
 * @property FCom_Core_Model_FieldOption FCom_Core_Model_FieldOption
 */
class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields_Options
    extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    use FCom_AdminSPA_AdminSPA_Controller_Trait_Form;

    static protected $_modelClass = FCom_Core_Model_FieldOption::class;
    static protected $_modelName = 'field_option';
    static protected $_recordName = 'Field Option';

    public function action_data()
    {
        $this->respond($this->getFormData());
    }

    public function getFormData()
    {
        $fieldId = $this->BRequest->get('id');
        $result = [];

        if ($fieldId) {
            $fieldOptions = $this->FCom_Core_Model_FieldOption
                ->orm()
                ->select(['id', 'label', 'swatch_info'])
                ->where('field_id', $fieldId);
            
            $result = $fieldOptions->find_many();
        }

        return $result;
    }
}