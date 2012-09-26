<?php

class FCom_Customer_ApiServer_V1_Address extends FCom_Admin_Controller_ApiServer_Abstract
{
    public function action_get()
    {
        $id = BRequest::i()->param('id');
        $customerId = BRequest::i()->param('customer_id');
        $len = BRequest::i()->get('len');
        if (!$len) {
            $len = 10;
        }
        $start = BRequest::i()->get('start');
        if (!$start) {
            $start = 0;
        }

        if ($id) {
            $customerAddress[] = FCom_Customer_Model_Address::load($id);
        } else if($customerId) {
            $customerAddress = FCom_Customer_Model_Address::orm()->where('customer_id', $customerId)->limit($len, $start)->find_many();
        } else {
            $customerAddress = FCom_Customer_Model_Address::orm()->limit($len, $start)->find_many();
        }
        if (empty($customerAddress)) {
            $this->ok();
        }
        $result = FCom_Customer_Model_Address::i()->prepareApiData($customerAddress);
        $this->ok($result);
    }

    public function action_post()
    {
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($post['customer_id'])) {
            $this->badRequest("Customer id is required");
        }


        $data = array();
        $data['customer_id'] = $post['customer_id'];

        if (!empty($post['firstname'])) {
            $data['firstname'] = $post['firstname'];
        }
        if (!empty($post['lastname'])) {
            $data['lastname'] = $post['lastname'];
        }
        if (!empty($post['street1'])) {
            $data['street1'] = $post['street1'];
        }
        if (!empty($post['street2'])) {
            $data['street2'] = $post['street2'];
        }
        if (!empty($post['city'])) {
            $data['city'] = $post['city'];
        }
        if (!empty($post['state'])) {
            $data['state'] = $post['state'];
        }
        if (!empty($post['zip'])) {
            $data['zip'] = $post['zip'];
        }
        if (!empty($post['country_code'])) {
            $data['country'] = $post['country_code'];
        }
        if (!empty($post['phone'])) {
            $data['phone'] = $post['phone'];
        }
        if (!empty($post['fax'])) {
            $data['fax'] = $post['fax'];
        }

        $address = FCom_Customer_Model_Address::orm()->create($data)->save();

        if (!$address) {
            $this->internalError("Can't create a customer address");
        }

        $this->created(array('id' => $address->id));
    }

    public function action_put()
    {
        $id = BRequest::i()->param('id');
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($id)) {
            $this->badRequest("Customer address id is required");
        }

        $data = array();

        if (!empty($post['firstname'])) {
            $data['firstname'] = $post['firstname'];
        }
        if (!empty($post['lastname'])) {
            $data['lastname'] = $post['lastname'];
        }
        if (!empty($post['street1'])) {
            $data['street1'] = $post['street1'];
        }
        if (!empty($post['street2'])) {
            $data['street2'] = $post['street2'];
        }
        if (!empty($post['city'])) {
            $data['city'] = $post['city'];
        }
        if (!empty($post['state'])) {
            $data['state'] = $post['state'];
        }
        if (!empty($post['zip'])) {
            $data['zip'] = $post['zip'];
        }
        if (!empty($post['country_code'])) {
            $data['country'] = $post['country_code'];
        }
        if (!empty($post['phone'])) {
            $data['phone'] = $post['phone'];
        }
        if (!empty($post['fax'])) {
            $data['fax'] = $post['fax'];
        }


        $address = FCom_Customer_Model_Address::load($id);
        if (!$address) {
            $this->notFound("Customer address id #{$id} not found");
        }

        $address->set($data)->save();
        $this->ok();
    }

    public function action_delete()
    {
        $id = BRequest::i()->param('id');

        if (empty($id)) {
            $this->notFound("Customer address id is required");
        }

        $address = FCom_Customer_Model_Address::load($id);
        if (!$address) {
            $this->notFound("Customer address id #{$id} not found");
        }

        $address->delete();
        $this->ok();
    }


}