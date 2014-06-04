<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_IndexTank_Index_Abstract extends BClass
{
    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Index_Abstract
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

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
