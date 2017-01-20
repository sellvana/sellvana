<?php

/**
 * Class Sellvana_Sales_Method_Payment_Abstract
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order_Payment_Transaction $Sellvana_Sales_Model_Order_Payment_Transaction
 */
abstract class Sellvana_Sales_Method_Payment_Abstract extends BClass implements
    Sellvana_Sales_Method_Payment_Interface
{
    /**
     * @var string
     */
    protected $_code;

    /**
     * @var Sellvana_Sales_Model_Order_Payment
     */
    protected $_payment;

    /**
     * @var Sellvana_Sales_Model_Order_Payment_Transaction
     */
    protected $_transaction;

    /**
     * @var array
     */
    protected $_details;

    /**
     * @var int
     */
    protected $_sortOrder = 50;

    /**
     * @var string
     */
    protected $_name;

    /**
     * @var array
     */
    protected $_config;

    /**
     * @var array
     */
    protected $_capabilities = [
        'pay'             => 1,
        'pay_offline'     => 0,
        'pay_online'      => 0,
        'pay_by_url'      => 0,
        'auth'            => 0,
        'auth_partial'    => 0,
        'reauth'          => 0,
        'void'            => 0,
        'void_online'     => 0,
        'capture'         => 0,
        'capture_partial' => 0,
        'refund'          => 0,
        'refund_partial'  => 0,
        'refund_online'   => 0,
        'recurring'       => 0,
    ];

    /**
     * @var bool
     */
    protected $_manualStateManagement = true;

    public function can($capability)
    {
        if (isset($this->_capabilities[strtolower($capability)])) {
            return (bool) $this->_capabilities[strtolower($capability)];
        }
        return false;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    public function asArray()
    {
        $result = $this->_details;
        $result['name'] = $this->getName();
        return $result;
    }

    public function set($name, $value = null)
    {
        if (is_array($name)) {
            if (true === $value) {
                $this->_details = $name;
            } else {
                foreach ($name as $k => $v) {
                    $this->_details[$k] = $v;
                }
            }
        } else {
            $this->_details[$name] = $value;
        }
        return $this;
    }

    public function get($name, $default = null)
    {
        return isset($this->_details[$name]) ? $this->_details[$name] : $default;
    }

    public function getDataToSave()
    {
        return [];
    }

    public function getPublicData()
    {
        return [];
    }

    public function getCheckoutFormPrefix()
    {
        return $this->_code;
    }

    public function getCheckoutFormView()
    {
        return $this->BViewEmpty;
    }

    public function setPaymentFormData(array $data)
    {
        $this->_details = $data;
        return $this;
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment $payment
     * @return mixed
     */
    public function payOnCheckout(Sellvana_Sales_Model_Order_Payment $payment)
    {
        return [];
    }

    public function payByUrl(Sellvana_Sales_Model_Order_Payment $payment)
    {
        return [];
    }

    public function payOffline(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        return [];
    }

    public function authorize(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        return [];
    }

    public function reauthorize(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        return [];
    }

    public function void(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        return [];
    }

    public function capture(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        return [];
    }

    public function refund(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        return [];
    }

    public function isAllDataPresent($data)
    {
        return true;
    }

    public function processReturnFromExternalCheckout()
    {
        return [];
    }

    public function getConfig($key = null)
    {
        if (empty($this->_config)) {
            $name = explode('_', get_class($this));
            $this->_config = $this->BConfig->get('modules/' . $name[0] . '_' . $name[1], []);
        }
        
        return null === $key ? $this->_config : (isset($this->_config[$key]) ? $this->_config[$key] : null);
    }
    
    public function isManualStateManagementAllowed()
    {
        $config = $this->getConfig();
        if (is_array($config) && array_key_exists('manual_state_management', $config)) {
            return $config['manual_state_management'];
        }

        return $this->_manualStateManagement;
    }

    public function isRootTransactionNeeded()
    {
        return false;
    }

    public function getRootTransactionType()
    {
        $labels = $this->Sellvana_Sales_Model_Order_Payment_Transaction->fieldOptions('transaction_type');
        return $labels[Sellvana_Sales_Model_Order_Payment_Transaction::CAPTURE];
    }

    public function getAllMetaInfo()
    {
        return [
            'capabilities' => $this->_capabilities,
            'is_manual_state_management_allowed' => $this->isManualStateManagementAllowed(),
            'is_root_transaction_needed' => $this->isRootTransactionNeeded(),
            'root_transaction_type' => $this->getRootTransactionType(),
        ];
    }

    /**
     * Shortcut for payment gateway error
     *
     * @param array $result
     * @param bool $setErrorState
     * @throws BException
     */
    protected function _setErrorStatus($result = null, $setErrorState = false)
    {
        $payment = $this->_payment;
        if (!$payment && $this->_transaction) {
            $payment = $this->_transaction->payment();
        }
        $this->Sellvana_Sales_Main->workflowAction('customerGetsPaymentError', [
            'payment' => $payment,
            'result' => $result,
            'setErrorState' => $setErrorState,
        ]);
        if ($this->_transaction) {
            $this->_transaction->setData('error', $result['error']['message']);
            $this->_transaction->save();
        }
        throw new BException($result['error']['message']);
    }

    public function __destruct()
    {
        unset($this->_payment, $this->_transaction);
    }
}
