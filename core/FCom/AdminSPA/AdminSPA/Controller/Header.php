<?php

class FCom_AdminSPA_AdminSPA_Controller_Header extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function action_search()
    {
        $result = [];
        try {
            $collect  = [];
            $priority = 1000000;
            $link      = '';
            $query = $this->BRequest->get('q');
            $this->BEvents->fire(__METHOD__, ['query' => $query, 'result' => &$collect]);
            foreach ($collect as $key) {
                if (is_array($key)) {
                    if (isset($key['priority']) && !empty($key['link']) && $key['priority'] < $priority) {
                        $priority = $key['priority'];
                        $link     = $key['link'];
                    }
                }
            }
            if ($link) {
                $this->ok();
                $result['collect'] = $collect;
                $result['link']    = $link;
            } else {
                $this->addMessage('Nothing found using query: ' . $this->BResponse->safeHtml($query), 'warning');
            }
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }
}