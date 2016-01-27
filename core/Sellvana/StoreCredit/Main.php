<?php

/**
 * Class Sellvana_StoreCredit_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */
class Sellvana_StoreCredit_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addWorkflow('Sellvana_StoreCredit_Workflow_StoreCredit');
        $this->Sellvana_Sales_Model_Cart->registerTotalRowHandler('Sellvana_StoreCredit_Model_Total_Cart');
    }
}