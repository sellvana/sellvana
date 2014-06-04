<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Admin_Controller_Dashboard extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $r = $this->BRequest;
        if (!$r->xhr()) {
            $this->BResponse->redirect('');
            return;
        }
        $widgets = $this->FCom_Admin_View_Dashboard->getWidgets();
        $widgetKeys = explode(',', $r->get('widgets'));
        $wrapped = $r->get('wrapped');
        $add = $r->get('add');
        $result = [];
        $persData = $this->FCom_Admin_Model_User->personalize();
        if ($add) {
            $pos = 100;
            if (!empty($persData['dashboard']['widgets'])) {
                foreach ($persData['dashboard']['widgets'] as $wKey => $wState) {
                    if (!empty($wState['pos']) && $wState['pos'] > $pos) {
                        $pos++;
                    }
                }
            }
            $persData = [];
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
            $result['widgets'][] = ['key' => $wKey, 'html' => $html];
            if ($add) {
                $persData['dashboard']['widgets'][$wKey]['closed'] = false;
                $persData['dashboard']['widgets'][$wKey]['collapsed'] = false;
                $persData['dashboard']['widgets'][$wKey]['pos'] = ++$pos;
            }
        }
        $result['filter'] = (isset($persData['dashboard']['filter'])) ? $persData['dashboard']['filter']: [];
        if ($add && $persData) {
            $this->FCom_Admin_Model_User->personalize($persData);
        }
        $this->BResponse->json($result);
    }

    public function action_data__POST()
    {
        $p = $this->BRequest->post();
        $persData = $this->FCom_Admin_Model_User->personalize();
        $persData['dashboard']['filter'] = $p;
        if ($p['range'] == 'range') {
            switch ($p['date']) {
                case 'last-month':
                    $p['min'] = date("Y-m-1", strtotime("last month"));
                    $p['max'] = date("Y-m-t", strtotime("last month"));
                    break;
                case 'last-week':
                    $p['min'] = date("Y-m-d", strtotime("last week"));
                    $p['max'] = date("Y-m-d", strtotime("last week + 7 days"));
                    break;
                case 'today':
                    $p['date'] = date("Y-m-d");
                    break;
                case 'all':
                    break;
                default:
                    $tmp = explode('~', $p['date']);
                    $p['min'] = $tmp[0];
                    $p['max'] = $tmp[1];
                    break;
            }
        }
        $widgets = $this->FCom_Admin_View_Dashboard->getWidgets();
        $result = [];
        $this->FCom_Admin_Model_User->personalize($persData);
        foreach ($widgets as $key => $widget) {
            if (isset($widget['async']) && $widget['async'] == true
                && isset($widget['filter']) && $widget['filter'] == true
            ) {
                if (!isset($widget['state']['closed']) || $widget['state']['closed']  == false) {
                    $widget['async'] = false;
                    $html = $this->view($widget['view'])->set('filter', $p)->render();
                    $result[] = ['key' => $key, 'html' => $html];
                }
            }
        }
        $this->BResponse->json($result);
    }
}
