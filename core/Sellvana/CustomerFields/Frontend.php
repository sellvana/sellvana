<?php

/**
 * Created by pp
 *
 * @project sellvana_core
 */
class Sellvana_CustomerFields_Frontend extends BClass
{
    public function hookEdit($args)
    {
        return print_r($args, 1);
        // todo load all custom fields allowed in edit form and render them
    }

    public function hookRegister($args)
    {
        return print_r($args, 1);
        // todo load all custom fields allowed in register form and render them
    }
}
