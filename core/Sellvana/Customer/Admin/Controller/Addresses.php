<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Customer_Admin_Controller_Addresses
 * @property Sellvana_Customer_Model_Address $Sellvana_Customer_Model_Address
 */
class Sellvana_Customer_Admin_Controller_Addresses extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'addresses';
    protected $_modelClass = 'Sellvana_Customer_Model_Address';
    protected $_gridTitle = 'Addresses';
    protected $_recordName = 'Address';
    protected $_mainTableAlias = 'a';

    public function gridConfig() {
        $config = parent::gridConfig();

        $config['columns'] = [
            ['name' => 'id', 'index' => 'a.id'],
            ['name' => 'firstname', 'index' => 'a.firstname'],
            ['name' => 'lastname', 'index' => 'a.lastname'],
            ['name' => 'company', 'index' => 'a.company'],
            ['name' => 'street1', 'index' => 'a.street1'],
            ['name' => 'street2', 'index' => 'a.street2'],
            ['name' => 'street3', 'index' => 'a.street3'],
            ['name' => 'country', 'index' => 'a.country'],
            ['name' => 'region', 'index' => 'a.region'],
            ['name' => 'city', 'index' => 'a.city'],
            ['name' => 'postcode', 'index' => 'a.postcode'],
            ['name' => 'phone', 'index' => 'a.phone'],
            ['name' => 'fax', 'index' => 'a.fax'],
            ['name' => 'email', 'index' => 'a.email']
        ];

        $config['filters'] = [
            ['field' => 'country', 'type' => 'multiselect'],
            ['field' => 'company', 'type' => 'text'],
            ['field' => 'postcode', 'type' => 'text'],
            ['field' => 'street1', 'type' => 'text'],
            ['field' => 'email', 'type' => 'text']
        ];

        return $config;
    }

    /**
     * config get all addresses of customer
     * @param $customer Sellvana_Customer_Model_Customer
     * @return array
     */
    public function getCustomerAddressesGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_addresses_grid_' . $customer->id;
        unset($config['form_url']);
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 115, 'buttons' => [['name' => 'edit', 'callback' => 'showModalToEditAddress'], ['name' => 'delete']]],
            ['name' => 'id', 'label' => 'ID', 'index' => 'a.id', 'width' => 80, 'hidden' => true],
            ['name' => 'customer_id', 'label' => 'Customer ID', 'index' => 'a.customer_id', 'hidden' => true,
                'form_hidden_label' => true, 'addable' => true, 'editable' => true,
                'element_print' => '<input name="customer_id" id="customer_id" type="hidden" value="' . $customer->id . '" />',
                'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'firstname', 'label' => 'First Name', 'index' => 'a.firstname', 'width' => 200,
                'addable' => true, 'editable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'lastname', 'label' => 'Last Name', 'index' => 'a.lastname', 'width' => 200,
                'addable' => true, 'editable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'company', 'label' => 'Company', 'index' => 'a.company', 'addable' => true,
                'editable' => true],
            ['type' => 'input', 'name' => 'street1', 'label' => 'Address Line 1', 'index' => 'a.street1', 'width' => 200,
                'addable' => true, 'editable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'street2', 'label' => 'Address Line 2', 'index' => 'a.street2', 'width' => 200,
                'hidden' => true, 'addable' => true, 'editable' => true],
            ['type' => 'input', 'name' => 'street3', 'label' => 'Address Line 3', 'index' => 'a.street3', 'width' => 200,
                'hidden' => true, 'addable' => true, 'editable' => true],
            ['type' => 'input', 'name' => 'country', 'label' => 'Country', 'index' => 'a.country', 'editor' => 'select',
                'addable' => true, 'options' => $this->BLocale->getAvailableCountries(), 'editable' => true,
                'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'region', 'label' => 'State/Province/Region', 'index' => 'a.region',
                'addable' => true, 'editable' => true, 'editor' => 'select',
                'options' =>  $this->BLocale->getAvailableRegions(),
//                'validation' => [ 'required' => true ],
            ],
            ['type' => 'input', 'name' => 'city', 'label' => 'City', 'index' => 'a.city', 'addable' => true,
                'editable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'postcode', 'label' => 'Zip/Postal Code', 'index' => 'a.postcode',
                'addable' => true, 'editable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'phone', 'label' => 'Phone', 'index' => 'a.phone', 'addable' => true,
                'editable' => true, 'hidden' => true],
            ['type' => 'input', 'name' => 'fax', 'label' => 'Fax', 'index' => 'a.fax', 'addable' => true,
                'editable' => true, 'hidden' => true],
            ['type' => 'input', 'name' => 'email', 'label' => 'Email', 'index' => 'a.email', 'width' => 100,
                'addable' => true, 'editable' => true, 'validation' => ['email' => true]],
            ['name' => 'is_default_billing', 'label' => 'Is Default Billing', 'display' => 'eval',
                'print' => '"<input type=\'radio\' value=\'"+rc.row["id"]+"\' name=\'model[default_billing_id]\' "+(rc.row["is_default_billing"] == 1 ? checked=\'checked\' : \'\')+" />"'
            ],
            ['name' => 'is_default_shipping', 'label' => 'Is Default Shipping', 'display' => 'eval',
                'print' => '"<input type=\'radio\' value=\'"+rc.row["id"]+"\' name=\'model[default_shipping_id]\' "+(rc.row["is_default_shipping"] == 1 ? checked=\'checked\' : \'\')+" />"'
            ]
        ];
        $config['actions'] = [
            'new'    => ['caption' => 'Add New Address', 'modal' => true],
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'country', 'type' => 'multiselect'],
            ['field' => 'company', 'type' => 'text'],
            ['field' => 'postcode', 'type' => 'text'],
            ['field' => 'street1', 'type' => 'text'],
            ['field' => 'email', 'type' => 'text']
        ];

        $config['orm'] = $this->Sellvana_Customer_Model_Address->orm($this->_mainTableAlias)
            ->select($this->_mainTableAlias . '.*')->where('customer_id', $customer->id);
        $config['data_url'] = $config['data_url'] . '?customer_id='.$customer->id;
        $config['callbacks'] = ['after_modalForm_render' => 'renderModalAddress'];
        $config['grid_before_create'] = 'customer_address_grid';
        return ['config' => $config];
    }

    public function getCustomerAddressesGridConfigForGriddle($customer) {
        $config = $this->getCustomerAddressesGridConfig($customer);

        unset($config['config']['actions']['new']);
        $config['config']['actions']['add-address'] = [
            'caption'  => 'Add New Address',
            'type'     => 'button',
            'id'       => 'add-address-from-grid',
            'class'    => 'btn-primary',
            'callback' => 'showModalToAddAddress'
        ];

        unset($config['config']['callbacks']);
        $config['config']['callbacks'] = [
            'componentDidMount' => 'addressGridRegister'
        ];

        unset($config['config']['grid_before_create']);
        $config['config']['grid_before_create'] = 'addressGridRegister';
        return $config;
    }

    public function gridOrmConfig($orm) {
        parent::gridOrmConfig($orm);
        if ($this->BRequest->get('customer_id')) {
            $orm->where('customer_id', $this->BRequest->get('customer_id'));
        }
    }

    public function action_get_state__POST()
    {
        $r = $this->BRequest;
        $result = [];
        $country = $r->post('country');
        if (!empty($country)) {
            $result = $this->BLocale->getAvailableRegions('name', $country);
        }
        $this->BResponse->json($result);
    }

    public function gridPostAfter($args)
    {

    }
}
