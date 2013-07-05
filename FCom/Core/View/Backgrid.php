<?php

class FCom_Core_View_Backgrid extends FCom_Core_View_Abstract
{
    protected function _applyPersonalization(&$config)
    {
        $gridId = !empty($config['personalize']['id']) ? $config['personalize']['id'] : $config['id'];

        // retrieve current personalization
        $pers = FCom_Admin_Model_User::i()->personalize();
        $pers = !empty($pers['grid'][$gridId]) ? $pers['grid'][$gridId] : array();

        $req = BRequest::i()->get();

        // prepare array to update personalization
        $personalize = array();
        foreach (array('p', 'ps', 's', 'sd', 'q') as $k) {
            if (!isset($pers[$k])) {
                $pers[$k] = null;
            }
            if (isset($req[$k]) && $pers[$k] !== $req[$k]) {
                $personalize[$k] = $req[$k];
            } elseif (isset($pers[$k])) {
                $config['state'][$k] = $pers[$k];
            }
        }

        if (!empty($pers['columns'])) {
            $persCols = $pers['columns'];
            foreach ($persCols as $k=>$c) {
                if (empty($config['columns'][$k])) {
                    unset($persCols[$k]);
                }
            }
        }
        // save personalization
        if (!empty($personalize)) {
            FCom_Admin_Model_User::i()->personalize(array('grid' => array($gridId => $personalize)));
        }

        // get columns personalization
        if (!empty($pers['columns'])) {
            $config['columns'] = BUtil::arrayMerge($config['columns'], $persCols);
            uasort($config['columns'], function($a, $b) { return $a['position'] - $b['position']; });
        }
    }

    public function getBackgridConfigJson()
    {
        $config = $this->grid['config'];
        $config['personalize_url'] = BApp::href('my_account/personalize');
        $config['container'] = '#'.$config['id'];

        $pos = 0;
        foreach ($config['columns'] as &$col) {
            $col['position'] = ($pos += 10);

            if (empty($col['cell'])) {
                if (!empty($col['href'])) {
                    $col['cell'] = new BValue('FCom.Backgrid.HrefCell');
                }
            }

            if (!empty($col['cell'])) {
                switch ($col['cell']) {
                case 'date': case 'datetime': //TODO: locale specific display format
                    $col['cell'] = new BValue("Backgrid.Extension.MomentCell.extend({
                        modelFormat:'YYYY-MM-DD',
                        displayFormat: 'M/D/YYYY',
                        displayInUTC: false
                    })");
                    break;
                }
            }
        }
        unset($col);

        //$this->_applyPersonalization($config);

        if (empty($config['state'])) {
            $config['state'] = BUtil::arrayMask(BRequest::i()->get(), 'p,ps,s,sd');
        }

        return BUtil::toJavaScript($config);
    }

    public function outputData()
    {
        $config = $this->grid['config'];
        $data = $this->grid['orm']->paginate();

        foreach ($data['rows'] as &$row) {
            foreach ($config['columns'] as $col) {
                if (!empty($col['cell']) && !empty($col['name'])) {
                    $field = $col['name'];
                    switch ($col['cell']) {
                        case 'number':
                            $row->$field = floatval($row->$field);
                            break;
                        case 'integer':
                            $row->$field = intval($row->$field);
                            break;
                    }
                }
            }
        }
        unset($row);

        BResponse::i()->json(array(
            array('c' => $data['state']['c']),
            BDb::many_as_array($data['rows']),
        ));
    }
}