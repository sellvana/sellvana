<?php

/**
 * Class Sellvana_Customer_AdminSPA_Controller_Customers
 *
 * @property Sellvana_Customer_Model_Customer Sellvana_Customer_Model_Customer
 * @property Sellvana_CustomerGroups_Model_Group Sellvana_CustomerGroups_Model_Group
 */
class Sellvana_Customer_AdminSPA_Controller_Customers extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig() 
    {
        $logInAsUrl = $this->BApp->href('customers/start_session?id=');
        $custGroupOptions = $this->Sellvana_CustomerGroups_Model_Group->groupsOptions();
        $custStatusOptions = $this->Sellvana_Customer_Model_Customer->fieldOptions('status');
        $countries = $this->BLocale->getAvailableCountries();

        return [
            'id' => 'customers',
            'data_url' => 'customers/grid_data',
            'columns' => [
                ['type' => 'row-select'],
                ['type' => 'actions', 'actions' => [
                    ['type' => 'edit', 'link' => '/customers/form?id={id}'],
                    ['name' => 'login', 'template' => "<a :href=\"'{$logInAsUrl}'+row.id\" target=\"AdminCustomer\" :title=\"'Log in as customer'|_\"><i class=\"fa fa-user\"></i></a>"],
                    ['type' => 'delete', 'delete_url' => 'customers/grid_delete'],
                ]],
                ['name' => 'thumb_url', 'label' => 'Thumbnail', 'datacell_template' => '<td><img :src="row.thumb_url"></td>', 'sortable' => false],
                ['name' => 'id', 'label' => 'ID', 'index' => 'c.id'],
                ['name' => 'firstname', 'label' => 'First Name', 'index' => 'c.firstname'],
                ['name' => 'lastname', 'label' => 'Last Name', 'index' => 'c.lastname'],
                ['name' => 'email', 'label' => 'Email', 'index' => 'c.email'],
                ['name' => 'customer_group', 'label' => 'Customer Group', 'index' => 'c.customer_group', 'options' => $custGroupOptions,
                    'edit' => ['bulk' => true, 'validation' => ['required' => true]],
                ],
                ['name' => 'status', 'label' => 'Status', 'index' => 'c.status', 'options' => $custStatusOptions,
                    'edit' => ['bulk' => true, 'validation' => ['required' => true]]
                ],
                ['name' => 'street1', 'label' => 'Address', 'index' => 'a.street1'],
                ['name' => 'city', 'label' => 'City', 'index' => 'a.city', 'hidden' => true],
                ['name' => 'region', 'label' => 'Region', 'index' => 'a.region', 'hidden' => true],
                ['name' => 'postcode', 'label' => 'Postal Code', 'index' => 'a.postcode', 'hidden' => true],
                ['name' => 'country', 'label' => 'Country', 'index' => 'a.country', 'hidden' => true, 'options' => $countries],
                ['name' => 'create_at', 'label' => 'Created', 'index' => 'c.create_at', 'format' => 'datetime'],
                ['name' => 'last_login', 'label' => 'Last Login', 'index' => 'c.last_login', 'hidden' => true, 'format' => 'datetime'],
            ],
            'filters' => [
                ['name' => 'firstname'],
                ['name' => 'lastname'],
                ['name' => 'email'],
                ['name' => 'customer_group'],
                ['name' => 'street1'],
                ['name' => 'city'],
                ['name' => 'region'],
                ['name' => 'postcode'],
                ['name' => 'create_at', 'type' => 'date'],
                ['name' => 'last_login', 'type' => 'date'],
                ['name' => 'country'],
                ['name' => 'status'],
            ],
            'bulk_actions' => [
                'edit'   => true,
                'delete' => true
            ]
        ];
    }

    public function getGridOrm()
    {
        return $this->Sellvana_Customer_Model_Customer->orm();
    }

    public function processGridPageData($data)
    {
        foreach ($data['rows'] as $row) {
            $row->set('thumb_url', $this->BUtil->gravatar($row->get('email'), 48));
        }
        return parent::processGridPageData($data);
    }

    public function action_grid_delete__POST()
    {

    }

    public function action_form_data()
    {
        $result = [];
        $pId = $this->BRequest->get('id');
        try {
            $customer = $this->Sellvana_Customer_Model_Customer->load($pId);
            if (!$customer) {
                throw new BException('Customer not found');
            }
            $result['form']['config']['tabs'] = $this->getFormTabs('/customers/form');
            $result['form']['config']['page_actions'] = $this->getDefaultFormPageActions();
            $result['form']['customer'] = $customer->as_array();

        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            $data = $this->BRequest->post();
            $this->ok()->addMessage('Customer was saved successfully', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }
    
    public function onHeaderSearch($args)
    {
        $r = $this->BRequest->get();
        if (isset($r['q']) && $r['q'] != '') {
            $value = '%' . $r['q'] . '%';
            $result = $this->Sellvana_Customer_Model_Customer->orm()
                ->where(['OR' => [
                    ['id like ?', (int)$value],
                    ['firstname like ?', (string)$value],
                    ['lastname like ?', (string)$value],
                    ['email like ?', (string)$value],
                ]])->find_one();
            $args['result']['customer'] = null;
            if ($result) {
                $args['result']['customer'] = [
                    'priority' => 10,
                    'link' => '/customers/form?id=' . $result->id(),
                ];
            }
        }
    }
}