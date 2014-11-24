<?php

/**
 * Created by
 * User: pp
 * Date: 24.Nov14
 */
class FCom_Promo_Admin_Controller_Conditions extends FCom_Admin_Controller_Abstract
{
    public function action_attributes_list()
    {
        return $this->BResponse->json(['total_count' => 50, 'items' => [['id'=>1,'text'=>'one'],['id'=>2,'text'=>'two'],['id'=>3,'text'=>'three']]]);
    }
}
