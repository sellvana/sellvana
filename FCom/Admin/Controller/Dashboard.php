<?php

class FCom_Admin_Controller_Dashboard extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        if (!BRequest::i()->xhr()) {
            BResponse::i()->redirect('');
        }
        $widgets = FCom_Admin_View_Dashboard::i()->getWidgets();
        $widgetKeys = explode(',', BRequest::i()->get('widgets'));
        $result = array();
        foreach ($widgetKeys as $wKey) {
            if (empty($widgets[$wKey])) {
                continue;
            }
            if (!empty($widgets[$wKey]['view'])) {
                $html = (string)$this->view($widgets[$wKey]['view']);
            } else {
                $html = $widgets[$wKey]['content'];
            }
            $result['widgets'][] = array('key' => $wKey, 'html' => $html);
        }
        BResponse::i()->json($result);
    }

    public function action_data()
    {

    }
}
