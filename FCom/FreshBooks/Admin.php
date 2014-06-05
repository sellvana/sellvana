<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_FreshBooks_Admin extends BClass
{
    public function bootstrap()
    {
        $this->BEvents->on('FCom_Sales_Model_Order::invoice', 'FCom_FreshBooks.createInvoiceFromOrder');

        $this->BLayout->addAllViews('Admin/views');
    }
}
