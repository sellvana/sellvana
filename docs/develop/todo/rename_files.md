Problem: PHP < 7.0 can't handle reserved words in namespaced class names.

Currently there's no problem since we're using underscore class names, but if/when we'll decide to switch to namespaced, this may present a problem.

Potentially: wait until PHP 7.0 is common 

Below is the list of all Abstract and Interface files:

    FCom_Shell_Action_Abstract
    FCom_Admin_View_Abstract
    FCom_Admin_Controller_Abstract
    FCom_ApiServer_Controller_Abstract
    FCom_Frontend_Controller_Abstract
    FCom_OAuth_Provider_Abstract
    FCom_PushServer_Service_Abstract
    FCom_Core_Controller_Abstract
    FCom_Core_Model_Abstract
    FCom_Core_View_Abstract
    Sellvana_CatalogIndex_Indexer_Abstract
    Sellvana_IndexTank_Index_Abstract
    Sellvana_MultiCurrency_RateProvider_Abstract
    Sellvana_Sales_Method_Checkout_Abstract
    Sellvana_Sales_Method_Payment_Abstract
    Sellvana_Sales_Method_Shipping_Abstract
    Sellvana_Sales_Model_Order_State_Abstract
    Sellvana_Sales_Model_Cart_Total_Abstract
    Sellvana_Sales_Model_Order_Total_Abstract
    Sellvana_Sales_Workflow_Abstract
    
    FCom_PushServer_Service_Interface
    Sellvana_CatalogIndex_Indexer_Interface
    Sellvana_Sales_Method_Checkout_Interface
    Sellvana_Sales_Method_Discount_Interface
    Sellvana_Sales_Method_Payment_Interface
    Sellvana_Sales_Method_Shipping_Interface
    Sellvana_Sales_Model_Cart_Total_Interface
    Sellvana_Sales_Model_Order_Total_Interface