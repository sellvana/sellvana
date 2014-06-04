<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Admin_Controller_HeaderSearch extends FCom_Admin_Controller_Abstract_GridForm
{
    public function action_index()
    {
        if ($this->BRequest->xhr()) {
            return ;
        }
        $result = [];
        $priority = 1000000;
        $url = '';
        $this->BEvents->fire(__METHOD__, ['result' => &$result]);
        foreach ($result as $key) {
            if (is_array($key)) {
                if (isset($key['priority']) && $key['url'] && $key['priority'] < $priority) {
                    $priority = $key['priority'];
                    $url = $key['url'];
                }
            }
        }
        if ($url != '') {
            $this->BResponse->redirect($url);
        }
        $this->layout('/header_search');
    }
}
