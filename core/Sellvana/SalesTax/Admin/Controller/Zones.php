<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_SalesTax_Admin_Controller_Zones
 *
 * @property Sellvana_SalesTax_Model_Zone $Sellvana_SalesTax_Model_Zone
 */
class Sellvana_SalesTax_Admin_Controller_Zones extends FCom_Admin_Controller_Abstract_GridForm {
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'salestax/zones';
    protected $_modelClass = 'Sellvana_SalesTax_Model_Zone';
    protected $_gridTitle = 'Tax Zones';
    protected $_recordName = 'Tax Zone';
    protected $_formTitleField = 'title';
    protected $_mainTableAlias = 'z';
    protected $_navPath = 'sales/tax/zones';
    protected $_permission = 'sales/tax/zones';

    public function gridConfig() {
        $config = parent::gridConfig();
        //unset($config['form_url']);

        $zoneTypeOptions = $this->Sellvana_SalesTax_Model_Zone->fieldOptions('zone_type');
        $countries = $this->FCom_Core_Main->getAllowedCountries();
        $config['id'] = 'tax-zone';
        $config['caption'] = 'Tax Zone';
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [['name' => 'edit'], ['name' => 'delete']]],
            ['name' => 'id', 'label' => 'ID', 'width' => 50],
            ['name' => 'title', 'label' => 'Title',
                'editable' => true, 'addable' => true],
            ['name' => 'zone_type', 'label' => 'Zone Type', 'options' => $zoneTypeOptions,
                'editor' => 'select', 'editable' => true, 'addable' => true, 'type' => 'select'],
            ['name' => 'country', 'label' => 'Country', 'options' => $countries,
                'editor' => 'select', 'editable' => true, 'addable' => true, 'type' => 'select'],
            ['name' => 'region', 'label' => 'Region',
                'editable' => true, 'addable' => true],
            ['name' => 'postcode_from', 'label' => 'From Postcode',
                'editable' => true, 'addable' => true],
            ['name' => 'postcode_to', 'label' => 'To Postcode',
                'editable' => true, 'addable' => true],
            ['name' => 'zone_rate_percent', 'label' => 'Zone Rate',
                'editable' => true, 'addable' => true],
        ];
        $config['actions'] = [
            #'new' => array('caption' => 'Add New Tax Zone', 'modal' => true),
            'edit' => true,
            'delete' => true,
        ];
        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
            ['field' => 'zone_type', 'type' => 'select'],
            ['field' => 'country', 'type' => 'select'],
            ['field' => 'region', 'type' => 'text'],
            ['field' => 'postcode_from', 'type' => 'text'],
            ['field' => 'postcode_to', 'type' => 'text'],
            ['field' => 'zone_rate_percent', 'type' => 'number-range'],
        ];
        $config['new_button'] = '#add_new_zone';

        if (!empty($config['orm'])) {
            if (is_string($config['orm'])) {
                $config['orm'] = $config['orm']::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias . '.*');
            }
            $this->gridOrmConfig($config['orm']);
        }

        return $config;
    }

    public function action_unique__POST() {
        $post = $this->BRequest->post();
        $data = each($post);
        $rows = $this->BDb->many_as_array($this->Sellvana_SalesTax_Model_Zone->orm()
                                                   ->where($data['key'], $data['value'])->find_many());
        $this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }
}
