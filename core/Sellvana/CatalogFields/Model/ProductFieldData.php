<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogFields_Model_ProductFieldData
 *
 * @property int $id
 * @property int $product_id
 * @property int $set_id
 * @property int $value_id
 * @property int $value_int
 * @property float $value_dec
 * @property string $value_var
 * @property string $value_text
 * @property string $value_date
 *
 * @property Sellvana_CatalogFields_Model_Field $Sellvana_CatalogFields_Model_Field
 * @property Sellvana_CatalogFields_Model_FieldOption $Sellvana_CatalogFields_Model_FieldOption
 */
class Sellvana_CatalogFields_Model_ProductFieldData extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product_field_data';

    /**
     * @param Sellvana_Catalog_Model_Product[] $products
     * @param array $fieldNames
     *
     * @return array
     */
    public function collectProductFieldsData($products, $fieldNames = [])
    {
        // transform collection into array of ids so that we'd be able to use it as filter further
        $fieldsData = $productIds = [];
        foreach ($products as $product) {
            $productIds[] = $product->get('id');
        }

        $orm = $this->Sellvana_CatalogFields_Model_Field->orm('f');
        if (count($fieldNames)) {
            $orm->where_in('field_code', $fieldNames);
        }

        $fields = $orm->find_many();
        $fieldIds = [];
        foreach ($fields as $field) {
            $fieldIds[] = $field->get('id');
        }

        // get all values for the relevant fields and store it for multiple uses
        $values = $this->Sellvana_CatalogFields_Model_FieldOption->orm('fo')
            ->where_in('field_id', $fieldIds)
            ->find_many();

        $options = [];
        foreach ($values as $val) {
            if (empty($options[$val->get('field_id')])) {
                $options[$val->get('field_id')] = [];
            }

            $options[$val->get('field_id')][$val->get('id')] = $val->get('label');
        }

        $orm->join(self::$_origClass, 'pf.field_id = f.id', 'pf')
            ->left_outer_join('Sellvana_CatalogFields_Model_FieldOption', 'fo.field_id = pf.field_id AND fo.id = pf.value_id', 'fo')
            ->left_outer_join('Sellvana_CatalogFields_Model_Set', 'fs.id = pf.set_id AND fs.set_type = "product"', 'fs')
            ->select(['pf.*', 'f.field_code', 'f.field_name', 'f.admin_input_type', 'f.table_field_type', 'fs.set_name'])
            ->where_in('pf.product_id', $productIds);

        $data = $orm->find_many();

        foreach ($data as $row) {
            $productId = $row->get('product_id');
            if (empty($fieldsData[$productId])) {
                $fieldsData[$productId] = [];
            }

            $fieldSetId = !is_null($row->get('set_id')) ? $row->get('set_id') : '';

            $inputType = $row->get('admin_input_type');
            if ($inputType == 'select') {
                $column = 'value_id';
            } else {
                $column = $this->Sellvana_CatalogFields_Model_Field->getTableColumn($row->get('table_field_type'));
            }
            $value = $row->get($column);

            $fieldId = $row->get('field_id');
            $field = [
                'id' => $fieldId,
                'field_code' => $row->get('field_code'),
                'field_name' => $row->get('field_name'),
                'admin_input_type' => $inputType,
                'value' => $value,
                'position' => $row->get('position'),
            ];

            if ($inputType == 'select' && !empty($options[$fieldId])) {
                $field['options'] = $options[$fieldId];
            } elseif ($inputType == 'select') {
                // hide the field if we don't have any option for it
                continue;
            }

            if (empty($fieldsData[$productId][$fieldSetId])) {
                $fieldsData[$productId][$fieldSetId] = [
                    'collapsed' => 'false',
                    'id' => $fieldSetId,
                    'set_name' => ($row->get('set_name')) ?: '',
                    'fields' => []
                ];
            }

            $fieldsData[$productId][$fieldSetId]['fields'][] = $field;

        }

        foreach ($products as $product) {
            $product->set('custom_fields', $fieldsData[$product->get('id')]);

            foreach ($fieldsData[$product->get('id')] as $fieldSetId => $fieldSet) {
                foreach ($fieldSet['fields'] as $field) {
                    $value = $field['value'];
                    $fieldId = $field['id'];
                    if (!empty($options[$fieldId])) {
                        $value = $options[$fieldId][$value];
                    }

                    $product->set($field['field_code'], $value);
                }
            }
        }
    }
}
