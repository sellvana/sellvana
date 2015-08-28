<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_Controller_Dashboard
 *
 * @property FCom_Admin_View_Dashboard $FCom_Admin_View_Dashboard
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
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

        $result['filter'] = (isset($persData['dashboard']['filter'])) ? $persData['dashboard']['filter']: [];
        if (!empty($result['filter']['range']) && $result['filter']['range'] == 'range') {
            $result['filter'] = $this->_calculateDate($result['filter']);
        }
        $this->_processDateFilter($result['filter']);

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
        if ($add && $persData) {
            $this->FCom_Admin_Model_User->personalize($persData);
        }
        $this->BResponse->json($result);
    }

    public function action_data__POST()
    {
        $p = $this->BRequest->post();
        if (!$this->validateDateTime()) {
            $persData = $this->FCom_Admin_Model_User->personalize();
            $persData['dashboard']['filter'] = $p;
            if ($p['range'] == 'range') {
                $p = $this->_calculateDate($p);
            }
            $this->_processDateFilter($p);
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
        } else {
            $result = ['error' => true];
        }

        $this->BResponse->json($result);
    }

    /**
     * @param array $filter
     */
    protected function _processDateFilter($filter)
    {
        if (empty($filter['date'])) {
            return;
        }

        $dayRecent = ($this->BConfig->get('modules/Sellvana_Sales/recent_day')) ? $this->BConfig->get('modules/Sellvana_Sales/recent_day') : 7;
        $params = [];
        if (strpos($filter['date'], '~') !== FALSE) {
            $range = explode('~', $filter['date']);
            $filter['date'] = array(
                'min' => $range[0],
                'max' => $range[1]
            );
        }
        switch ($filter['type']) {
            case 'equal':
                $from = $filter['date'];
                if ($filter['date'] == 'today') {
                    $from = date('Y-m-d');
                    $cond = '> ?';
                    $params[] = $from;
                } else {
                    $from = strtotime($from);
                    $to = $from + 24 * 60 * 60 - 1;
                    $cond = 'BETWEEN ? AND ?';
                    $params[] = $from;
                    $params[] = $to;
                }
                break;
            case 'from':
                $cond = '> ?';
                $params[] = $filter['date'];
                break;
            case 'to':
                $cond = '< ?';
                $params[] = strtotime($filter['date']) + 24 * 60 * 60;
                break;
            case 'between':
                switch ($filter['date']) {
                    case 'last-month':
                        $cond = '> DATE_SUB(NOW(), INTERVAL 1 MONTH)';
                        break;
                    case 'last-week':
                        $cond = '> DATE_SUB(NOW(), INTERVAL 7 DAY)';
                        break;
                    default:
                        if (!empty($filter['date']['min']) && !empty($filter['date']['max'])) {
                            $cond = 'BETWEEN ? AND ?';
                            $params[] = $filter['date']['min'];
                            $params[] = $filter['date']['max'];
                        } else {
                            $cond = '> DATE_SUB(NOW(), ? DAY)';
                            $params[] = $dayRecent;
                        }
                }
                break;
            case 'not-in':
                if (!empty($filter['date']['min']) && !empty($filter['date']['max'])) {
                    $cond = 'NOT BETWEEN ? AND ?';
                    $params[] = $filter['date']['min'];
                    $params[] = $filter['date']['max'];
                } else {
                    $cond = '> DATE_SUB(NOW(), ? DAY)';
                    $params[] = $dayRecent;
                }
                break;
            case 'default':
                $cond = '> 0';
                break;
            default:
                $cond = '> DATE_SUB(NOW(), ? DAY)';
                $params[] = $dayRecent;
        }
        $this->BApp->set('dashboard_date_filter', [
            'condition' => $cond,
            'params' => $params
        ]);
    }

    /**
     * @return bool
     */
    public function validateDateTime()
    {
        $p = $this->BRequest->post();
        $error = false;
        if (!in_array($p['range'], ['range', 'not_range'])) {
            $error = true;
        }
        switch ($p['date']) {
            case 'last-month':case 'last-week':case 'today': case 'all':
                if (empty($p['is_btn_filter'])) {
                    $error = true;
                }
            break;
            default:
                $tmp = explode('~', $p['date']);
                if (($p['range'] == 'range' && count($tmp) < 2) || ($p['range'] == 'not_range' && count($tmp) > 1)) {
                    $error = true;
                }
                if (!$error) {
                    foreach ($tmp as $value) {
                        if (!preg_match('/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/', $value)) {
                            $error = true;
                        }
                    }
                }
                break;
        }
        return $error;
    }

    /**
     * @param $p
     * @return mixed
     */
    protected function _calculateDate($p)
    {
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
        return $p;
    }
}
