<?php

class Sellvana_IndexTank_Index_Abstract extends BClass
{
    /**
     * @param $orm
     * @param $r
     * @param array $d
     * @return mixed
     */
    public function paginate($orm, $r, $d = [])
    {
        $rbak = $r;
        $r['sc'] = null;
        $d['donotlimit'] = true;
        $res = $orm->paginate($r, $d);
        $res['state']['sc'] = !empty($rbak['sc']) ? $rbak['sc'] : '';
        return $res;
    }
}
