<?php

class FCom_Admin_Controller_Dashboard extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $r = BRequest::i();
        if (!$r->xhr()) {
            BResponse::i()->redirect('');
        }
        $widgets = FCom_Admin_View_Dashboard::i()->getWidgets();
        $widgetKeys = explode(',', $r->get('widgets'));
        $wrapped = $r->get('wrapped');
        $add = $r->get('add');
        $result = array();
        if ($add) {
            $persData = FCom_Admin_Model_User::i()->personalize();
            $pos = 100;
            if (!empty($persData['dashboard']['widgets'])) {
                foreach ($persData['dashboard']['widgets'] as $wKey => $wState) {
                    if (!empty($wState['pos']) && $wState['pos'] > $pos) {
                        $pos++;
                    }
                }
            }
            $persData = array();
        }
        foreach ($widgetKeys as $wKey) {
            if (empty($widgets[$wKey])) {
                continue;
            }
            if (!$wrapped) {
                if (!empty($widgets[$wKey]['view'])) {
                    $html = (string)$this->view($widgets[$wKey]['view']);
                } else {
                    $html = $widgets[$wKey]['content'];
                }
            } else {
                $widgets[$wKey]['async'] = false;
                $html = $this->view('dashboard/widget')->set('widget', $widgets[$wKey])->render();
            }
            $result['widgets'][] = array('key' => $wKey, 'html' => $html);
            if ($add) {
                $persData['dashboard']['widgets'][$wKey]['closed'] = false;
                $persData['dashboard']['widgets'][$wKey]['collapsed'] = false;
                $persData['dashboard']['widgets'][$wKey]['pos'] = ++$pos;
            }
        }
        if ($add && $persData) {
            FCom_Admin_Model_User::i()->personalize($persData);
        }
        BResponse::i()->json($result);
    }

    public function action_data()
    {

    }
}
