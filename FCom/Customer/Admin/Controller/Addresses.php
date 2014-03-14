<?php

class FCom_Customer_Admin_Controller_Addresses extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'addresses';
    protected $_modelClass = 'FCom_Customer_Model_Address';
    protected $_gridTitle = 'Addresses';
    protected $_recordName = 'Address';
    protected $_mainTableAlias = 'a';

    /**
     * config get all addresses of customer
     * @param $customer FCom_Customer_Model_Customer
     * @return array
     */
    public function getCustomerAddressesGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_addresses_grid_' . $customer->id;
        $config['columns'] = array(
            array('type'=>'row_select'),
            array('name' => 'id', 'label' => 'ID', 'index' => 'a.id', 'width' => 80, 'hidden' => true),
            array('name' => 'customer_id', 'label' => 'Customer ID', 'index' => 'a.customer_id', 'hidden' => true, 'form_hidden_label' => true,
                  'addable' => true, 'editable' => true,
                  'element_print' => '<input name="customer_id" id="customer_id" type="hidden" value="'.$customer->id.'" />',
                  'validation' => array('required' => true)),
            array('type'=>'input', 'name' => 'firstname', 'label' => 'First Name', 'index' => 'a.firstname', 'width' => 200, 'addable' => true, 'editable' => true,
                  'validation' => array('required' => true)),
            array('type'=>'input', 'name' => 'lastname', 'label' => 'Last Name', 'index' => 'a.lastname', 'width' => 200, 'addable' => true, 'editable' => true,
                  'validation' => array('required' => true)),
            array('type'=>'input', 'name' => 'company', 'label' => 'Company', 'index' => 'a.company', 'addable' => true, 'editable' => true),
            array('type'=>'input', 'name' => 'street1', 'label' => 'Address Line 1', 'index' => 'a.street1', 'width' => 200, 'addable' => true, 'editable' => true,
                  'validation' => array('required' => true)),
            array('type'=>'input', 'name' => 'street2', 'label' => 'Address Line 2', 'index' => 'a.street2', 'width' => 200, 'hidden' => true, 'addable' => true, 'editable' => true),
            array('type'=>'input', 'name' => 'street3', 'label' => 'Address Line 3', 'index' => 'a.street3', 'width' => 200, 'hidden' => true, 'addable' => true, 'editable' => true),
            array('type'=>'input', 'name' => 'country', 'label' => 'Country', 'index' => 'a.country', 'editor' => 'select', 'addable' => true,
                  'options' => FCom_Geo_Model_Country::i()->options(), 'editable' => true,
                  'validation' => array('required' => true)),
            array('type'=>'input', 'name' => 'region', 'label' => 'State/Province/Region', 'index' => 'a.region', 'addable' => true, 'editable' => true, 'editor' => 'select',
                'options' => FCom_Geo_Model_Region::i()->options('US'),
//                'validation' => array('required' => true)),
            ),
            array('type'=>'input', 'name' => 'city', 'label' => 'City', 'index' => 'a.city', 'addable' => true, 'editable' => true,
                  'validation' => array('required' => true)),
            array('type'=>'input', 'name' => 'postcode', 'label' => 'Zip/Postal Code', 'index' => 'a.postcode', 'addable' => true, 'editable' => true,
                  'validation' => array('required' => true)),
            array('type'=>'input', 'name' => 'phone', 'label' => 'Phone', 'index' => 'a.phone', 'addable' => true, 'editable' => true, 'hidden' => true,
                  'validation' => array('required' => true)),
            array('type'=>'input', 'name' => 'fax', 'label' => 'Fax', 'index' => 'a.fax', 'addable' => true, 'editable' => true, 'hidden' => true),
            array('type'=>'input', 'name' => 'email', 'label' => 'Email', 'index' => 'a.email', 'width' => 100, 'addable' => true, 'editable' => true,
                  'validation' => array('required' => true, 'email' => true)),
            array('type'=>'btn_group', 'name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 115,
                  'buttons' => array(
									array('name'=>'edit'),
									array('name' => 'delete')
								)
				),
        );
        $config['actions'] = array(
            'new'    => array('caption' => 'Add New Address', 'modal' => true),
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'country', 'type' => 'multiselect'),
            array('field' => 'company', 'type' => 'text'),
            array('field' => 'postcode', 'type' => 'text'),
            array('field' => 'street1', 'type' => 'text'),
            array('field' => 'email', 'type' => 'text'),
            '_quick' => array('expr' => 'street1 like ? or company like ? or city like ? or country like ?', 'args' => array('%?%', '%?%', '%?%', '%?%'))
        );

        $config['orm'] = FCom_Customer_Model_Address::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias.'.*')->where('customer_id', $customer->id);
        $callbackModal = "
            $('#country').on('change',function () {
                getState($(this).val());
            });
            getState($('#country').val());
            function getState (country) {
                $.post('".BApp::i()->href('addresses/get_state')."', {country: country}).done(function (data) {
                            var region = $('#region');
                            if (!$.isEmptyObject(data)) {
                                region.html('');
                                region.parents('div.form-group').show();
                                for (var i in data ) {
                                    var option = '<option value='+ i +'>' + data[i] + '</option>';
                                    region.append(option);
                                }
                            } else {
                                region.html('<option value=\" \"></option>');
                                region.parents('div.form-group').hide();
                            };
                    });
            };
        ";
        $config['callbacks'] = array('after_modalForm_render' => $callbackModal);
        return array('config' => $config);
    }

    public function action_get_state()
    {
        $r = BRequest::i();
        $result = array();
        $country = $r->post('country');
        if (!empty($country)) {
            $result = FCom_Geo_Model_Region::i()->options($country);
        }
        BResponse::i()->json($result);
    }
}
