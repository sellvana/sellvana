<?php

class FCom_IndexTankAdmin extends BClass
{
    static public function bootstrap()
    {
        $module = BApp::m();
        $module->base_src .= '/Admin';

        BFrontController::i()
            ->route('GET|POST /indextank/settings', 'FCom_IndexTank_Admin_Controller_Settings.index')
        ;
        BLayout::i()
                ->addAllViews('Admin/views');

        BPubSub::i()
            ->on('FCom_Admin_Controller_Settings::action_index__POST', 'FCom_IndexTankAdmin.onSettingsPost')
            ->on('BLayout::theme.load.after', 'FCom_IndexTankAdmin::layout')
        ;
    }
}