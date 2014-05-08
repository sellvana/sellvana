<?php

class FCom_Admin_Controller_HeaderSearch extends FCom_Admin_Controller_Abstract_GridForm
{
    public function action_index()
    {
        if (BRequest::i()->xhr()) {
            return ;
        }
        $result = [];
        $priority = 1000000;
        $url = '';
        BEvents::i()->fire(__METHOD__, ['result' => &$result]);
        foreach ($result as $key) {
            if (is_array($key)) {
                if (isset($key['priority']) && $key['url'] && $key['priority'] < $priority) {
                    $priority = $key['priority'];
                    $url = $key['url'];
                }
            }
        }
        if ($url != '') {
            BResponse::i()->redirect($url);
        }
        $this->layout('/header_search');
    }
}
