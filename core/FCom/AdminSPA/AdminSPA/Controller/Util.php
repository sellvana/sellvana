<?php

class FCom_AdminSPA_AdminSPA_Controller_Util extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{

    public function action_personalize__POST()
    {
        $r = $this->BRequest->request();
        $data = [];
        if (empty($r['do'])) {
            $this->BResponse->json(['error' => true, 'r' => $r]);
            return;
        }
        switch ($r['do']) {
            case 'grid.col.hidden':
                if (empty($r['grid']) || empty($r['col']) || !isset($r['hidden'])) {
                    break;
                }
                $columns = [$r['col'] => ['hidden' => !empty($r['hidden']) && $r['hidden'] !== 'false']];
                $data = ['grid' => [$r['grid'] => ['columns' => $columns]]];

                break;

            case 'grid.col.order':
                if (is_array($r['cols'])) {
                    $cols = $r['cols'];
                } else {
                    $cols = $this->BUtil->fromJson($r['cols']);
                }

                $columns = [];
                foreach ($cols as $i => $col) {
                    if (empty($col['name'])) {
                        continue;
                    }
                    $columns[$col['name']] = ['position' => $col['position']];
                }
                $data = ['grid' => [$r['grid'] => ['columns' => $columns]]];

                break;

            case 'settings.tabs.order':
                break;

            case 'settings.sections.order':
                break;

            case 'nav.collapse':
                $data['nav']['collapsed'] = !empty($r['collapsed']);
                break;

            case 'dashboard.widget.pos':
                if (empty($r['widgets'])) {
                    break;
                }
                foreach ($r['widgets'] as $i => $wKey) {
                    $data['dashboard']['widgets'][$wKey]['pos'] = $i + 1;
                }
                break;

            case 'dashboard.widget.close': case 'dashboard.widget.collapse':
            if (empty($r['key'])) {
                break;
            }
            $data = [];
            if ($r['do'] == 'dashboard.widget.close') {
                $data['closed'] = true;
            }
            if ($r['do'] == 'dashboard.widget.collapse') {
                $data['collapsed'] = !empty($r['collapsed'])
                    && $r['collapsed'] !== '0'
                    && $r['collapsed'] !== 'false';
            }
            $data = ['dashboard' => ['widgets' => [$r['key'] => $data]]];
            break;
        }
        $this->BEvents->fire(__METHOD__, ['request' => $r, 'data' => &$data]);

        $this->FCom_Admin_Model_User->personalize($data);

        $this->addResponses(['data' => $data, 'r' => $r]);
        $this->ok()->respond();
    }
}