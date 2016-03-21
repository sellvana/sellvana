<?php

/**
 * Class Sellvana_ShippingFedex_ShippingMethod
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_ShippingFedex_ShippingMethod extends Sellvana_Sales_Method_Shipping_Abstract
{
    protected $_name       = 'FedEx';
    protected $_code       = 'fedex';
    protected $_configPath = 'modules/Sellvana_ShippingFedex';

    protected $_configCache = null;

    protected $_clientCache = [];

    protected $_requestData = [];

    protected function _fetchRates($data)
    {
        $this->_requestData = $data;
        $request = $this->_buildRequest();
        $request = array_merge($request, [
            'Version' => [
                'ServiceId' => 'crs',
                'Major' => '18',
                'Intermediate' => '0',
                'Minor' => '0'
            ],
            'TransactionDetail' => [
                'CustomerTransactionId' => $this->_data('trans_id'),
            ],
            'ReturnTransmitAndCommit' => true,
            'RequestedShipment' => [
                'DropoffType' => 'REGULAR_PICKUP',
                'ShipTimestamp' => date('c'),
                'ServiceType' => 'INTERNATIONAL_PRIORITY',
                'PackagingType' => 'YOUR_PACKAGING',
                'TotalInsuredValue' => [
                    'Amount' => $this->_data('amount'),
                    'Currency' => 'USD',
                ],
                'Shipper' => [
                    'Contact' => [
                        'PersonName' => $this->_data('shipper/name'),
                        'CompanyName' => $this->_data('shipper/company'),
                        'PhoneNumber' => $this->_data('shipper/phone'),
                    ],
                    'Address' => [
                        'StreetLines' => [
                            $this->_data('shipper/street1'),
                            $this->_data('shipper/street2'),
                            $this->_data('shipper/street3'),
                        ],
                        'City' => $this->_data('shipper/city'),
                        'StateOrProvinceCode' => $this->_data('shipper/region'),
                        'PostalCode' => $this->_data('shipper/postcode'),
                        'CountryCode' => $this->_data('shipper/country'),
                        'Residential' => $this->_data('shipper/residential', 1),
                    ],
                ],
                'Recipient' => [
                    'Contact' => [
                        'PersonName' => $this->_data('recipient/name'),
                        'CompanyName' => $this->_data('recipient/company'),
                        'PhoneNumber' => $this->_data('recipient/phone'),
                    ],
                    'Address' => [
                        'StreetLines' => [
                            $this->_data('recipient/street1'),
                            $this->_data('recipient/street2'),
                            $this->_data('recipient/street3'),
                        ],
                        'City' => $this->_data('recipient/city'),
                        'StateOrProvinceCode' => $this->_data('recipient/region'),
                        'PostalCode' => $this->_data('recipient/postcode'),
                        'CountryCode' => $this->_data('recipient/country'),
                        'Residential' => $this->_data('recipient/residential', 1),
                    ],
                ],
                'ShippingChargesPayment' => [
                    'PaymentType' => $this->_data('payment_type', 'SENDER'),
                    'Payor' => [
                        'ResponsibleParty' => [
                            'AccountNumber' => $this->_data('billaccount'),
                            'Contact' => null,
                            'Address' => [
                                'CountryCode' => $this->_data('payor/country')
                            ],
                        ],
                    ],
                ],
                'PackageCount' => 1,
                'RequestedPackageLineItems' => [
                    'SequenceNumber' => 1,
                    'GroupPackageCount' => 1,
                    'Weight' => [
                        'Value' => 50.0,
                        'Units' => 'LB'
                    ],
                    'Dimensions' => [
                        'Length' => 108,
                        'Width' => 5,
                        'Height' => 5,
                        'Units' => 'IN'
                    ],
                ],
            ],
        ]);
    }

    protected function _getSoapClient($service)
    {
        $serviceKey = strtolower($service);
        if (!empty($this->_clientCache[$serviceKey])) {
            return $this->_clientCache[$serviceKey];
        }

        $modRootDir = $this->BModuleRegistry->module('Sellvana_ShippingFedex')->root_dir;
        $files = glob("{$modRootDir}/wsdl/{$service}Service_v*.wsdl");
        if (!$files) {
            throw new BException('Invalid service: ' . $service);
        }

        ini_set('soap.wsdl_cache_enabled', '0');

        $soapParams = [
            'trace' => 1,
        ];
        $this->_clientCache[$serviceKey] = new SoapClient($files[0], $soapParams);

        return $this->_clientCache[$serviceKey];
    }

    protected function _data($path, $default = null)
    {
        $result = $this->BUtil->dataGet($this->_requestData, $path);
        if (null === $result) {
            $result = $this->BConfig->get("modules/Sellvana_ShippingFedex/{$path}");
        }
        if (null === $result) {
            $result = $default;
        }
        return $result;
    }

    protected function _buildRequest()
    {
        if (!$this->_data('key') || !$this->_data('password')) {
            $result = [
                'error' => 1,
                'message' => 'Incomplete FedEx User Authentication configuration',
            ];
            return $result;
        }

        $request = [
            'ParentCredential' => [
                'Key' => $this->_data('parentkey'),
                'Password' => $this->_data('parentpassword')
            ],
            'UserCredential' => [
                'Key' => $this->_data('key'),
                'Password' => $this->_data('password')
            ],
            'ClientDetail' => [
                'AccountNumber' => $this->_data('shipaccount'),
                'MeterNumber' => $this->_data('meter'),
            ],
        ];

        return $request;
    }
}