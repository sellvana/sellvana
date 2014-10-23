<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_IndexTank_Index_Abstract extends BClass
{
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
