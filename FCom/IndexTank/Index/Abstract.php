<?php

class FCom_IndexTank_Index_Abstract extends BClass
{
    public function paginate($orm, $r, $d=array())
    {
        $rbak = $r;
        $r['sc'] = null;
        $res = $orm->paginate($r, $d);
        $res['state']['sc'] = $rbak['sc'];
        return $res;
    }
}