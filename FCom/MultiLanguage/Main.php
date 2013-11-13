<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_MultiLanguage_Main extends BClass
{
    public static function bootstrap()
    {
        $req = BRequest::i();
        $lang = $req->request("lang");
        if(!empty($lang)){
            BSession::i()->set('_language', $lang);
        }
    }
}