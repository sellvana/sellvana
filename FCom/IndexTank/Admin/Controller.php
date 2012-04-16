<?php
class FCom_IndexTank_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_dashboard()
    {
        $status = FCom_IndexTank_Index_Product::i()->status();
        BLayout::i()->view('indextank/dashboard')->set('status', $status);
        $this->layout('/indextank/dashboard');
    }
}