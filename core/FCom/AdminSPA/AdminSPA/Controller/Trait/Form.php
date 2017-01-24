<?php

trait FCom_AdminSPA_AdminSPA_Controller_Trait_Form
{
    public function getFormTabs($path)
    {
        $this->layout($path);
        return $this->view('app')->getFormTabs($path);
    }
}