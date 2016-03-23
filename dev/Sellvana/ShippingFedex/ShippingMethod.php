<?php

/**
 * Class Sellvana_ShippingFedex_ShippingMethod
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_ShippingFedex_ShippingMethod extends Sellvana_Sales_Method_Shipping_Abstract
{
    const SERVICE_RATE = 'Rate';

    protected $_name       = 'FedEx';
    protected $_code       = 'fedex';
    protected $_configPath = 'modules/Sellvana_ShippingFedex';

    /**
     * @var null
     */
    protected $_configCache = null;

    /**
     * Storage for SOAP clients
     *
     * @var array
     */
    protected $_clientCache = [];

    /**
     * @var array
     */
    protected $_requestData = [];

    /**
     * XPaths to data we need to get from WSDL files
     *
     * @var array
     */
    protected static $_wsdlMap = [
        'ServiceId' => [
            'path' => "//xs:complexType[@name='VersionId']/*/xs:element[@name='ServiceId']",
            'attr' => 'fixed'
        ],
        'Major' => [
            'path' => "//xs:complexType[@name='VersionId']/*/xs:element[@name='Major']",
            'attr' => 'fixed'
        ],
        'Intermediate' => [
            'path' => "//xs:complexType[@name='VersionId']/*/xs:element[@name='Intermediate']",
            'attr' => 'fixed'
        ],
        'Minor' => [
            'path' => "//xs:complexType[@name='VersionId']/*/xs:element[@name='Minor']",
            'attr' => 'fixed'
        ],
    ];

    protected static $_wsdlConfig = [];

    protected static $_timeConst = [
        'TWO_DAYS' => 2,
        'THREE_DAYS' => 3,
    ];

    protected function _fetchRates($data)
    {
        $this->_requestData = array_merge($this->_requestData, $data);
        $rateClient = $this->_getSoapClient(self::SERVICE_RATE);

        $request = $this->_buildRequest();
        $dimensions = explode('x', $this->_data('package_size'));
        if (count($dimensions) !== 3) {
            $result = [
                'error' => 1,
                'message' => 'Dimensions in wrong format (Product ID: ' . array_shift($data['items']) . ')',
            ];
            return $result;
        }

        $catalogConfig = $this->BConfig->get('modules/Sellvana_Catalog');

        $request = array_merge($request, [
            'ReturnTransitAndCommit' => true,
            'RequestedShipment' => [
                'DropoffType' => $this->_data('dropoff_location'),
                'ShipTimestamp' => date('c'),
                'PackagingType' => 'YOUR_PACKAGING',
                'Shipper' => [
                    'Contact' => [
                        'CompanyName' => $this->BConfig->get("modules/Sellvana_Sales/store_name"),
                        'PhoneNumber' => $this->BConfig->get("modules/Sellvana_Sales/store_phone"),
                    ],
                    'Address' => [
                        'StreetLines' => [
                            $this->BConfig->get("modules/Sellvana_Sales/store_street1"),
                            $this->BConfig->get("modules/Sellvana_Sales/store_street2"),
                        ],
                        'City' => $this->BConfig->get("modules/Sellvana_Sales/store_city"),
                        'StateOrProvinceCode' => $this->BConfig->get("modules/Sellvana_Sales/store_region"),
                        'PostalCode' => $this->BConfig->get("modules/Sellvana_Sales/store_postcode"),
                        'CountryCode' => $this->BConfig->get("modules/Sellvana_Sales/store_country"),
                    ],
                ],
                'Recipient' => [
                    'Contact' => [
                        'PhoneNumber' => $this->_data('to_phone'),
                    ],
                    'Address' => [
                        'StreetLines' => [
                            $this->_data('to_street1'),
                            $this->_data('to_street2'),
                        ],
                        'City' => $this->_data('to_city'),
                        'StateOrProvinceCode' => substr($this->_data('to_region'), 0, 2),
                        'PostalCode' => $this->_data('to_postcode'),
                        'CountryCode' => $this->_data('to_country'),
                    ],
                ],
                'ShippingChargesPayment' => [
                    'PaymentType' => 'SENDER',
                    'Payor' => [
                        'ResponsibleParty' => [
                            'AccountNumber' => $this->_data('shipper_number'),
                            'Contact' => null,
                            'Address' => [
                                'CountryCode' => $this->BConfig->get("modules/Sellvana_Sales/store_country")
                            ],
                        ],
                    ],
                ],
                'LabelSpecification' => [
                    'LabelFormatType' => 'COMMON2D',
                    'ImageType' => 'PNG',
                    'LabelStockType' => 'PAPER_8.5X11_TOP_HALF_LABEL',
                ],
                'PackageCount' => 1,
                'RequestedPackageLineItems' => [
                    'SequenceNumber' => 1,
                    'GroupPackageCount' => 1,
                    'Weight' => [
                        'Value' => $this->_data('weight'),
                        'Units' => strtoupper($catalogConfig['weight_unit'])
                    ],
                    'Dimensions' => [
                        'Length' => $dimensions[0],
                        'Width' => $dimensions[1],
                        'Height' => $dimensions[2],
                        'Units' => strtoupper($catalogConfig['length_unit'])
                    ],
                ],
            ],
        ]);

        if ($this->_data('insurance')) {
            $request['TotalInsuredValue'] = [
                'Amount' => $this->_data('amount'),
                'Currency' => $this->BConfig->get('modules/FCom_Core/base_currency'),
            ];
        }

        $rates = $rateClient->getRates($request);

        if ($rates->HighestSeverity == 'ERROR') {
            $message = '';
            if (is_array($rates->Notifications)) {
                foreach ($rates->Notifications as $notification) {
                    $message .= $notification->LocalizedMessage;
                }
            } else {
                $message = $rates->Notifications->LocalizedMessage;
            }
            $result = [
                'error' => 1,
                'message' => $message,
            ];
            return $result;
        }

        $result = [
            'success' => 1,
            'rates' => []
        ];

        foreach ($rates->RateReplyDetails as $service) {
            $serviceType = $service->ServiceType;
            $details = (is_array($service->RatedShipmentDetails)) ? $service->RatedShipmentDetails[0] : $service->RatedShipmentDetails;
            $amount = $details->ShipmentRateDetail->TotalNetCharge->Amount;

            $time = false;
            if (array_key_exists('DeliveryTimestamp', $service)) {
                $time = $service->DeliveryTimestamp;
            } elseif (array_key_exists('TransitTime', $service)) {
                $time = $service->TransitTime;
            }

            $result['rates']['_' . $serviceType] = [];
            if (!empty(self::$_timeConst[$time])) {
                $days = self::$_timeConst[$time];
                $result['rates']['_' . $serviceType]['max_days'] = $days;
            } else {
                try {
                    new DateTime($time);
                    $result['rates']['_' . $serviceType]['exact_time'] = $time;
                } catch (Exception $e) {
                    $result['rates']['_' . $serviceType]['max_days'] = 3;
                }
            }
            $result['rates']['_' . $serviceType]['price'] = $amount;
        }

        return $result;
    }

    /**
     * @param string $service
     * @return SoapClient
     * @throws BException
     */
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
        $this->_parseWsdl($service, $files[0]);

        return $this->_clientCache[$serviceKey];
    }

    protected function _data($path, $default = null)
    {
        $result = $this->BUtil->arrayGet($this->_requestData, $path);
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
        if (!$this->_data('access_key') || !$this->_data('password')) {
            $result = [
                'error' => 1,
                'message' => 'Incomplete FedEx User Authentication configuration',
            ];
            return $result;
        }

        $request = [
            'WebAuthenticationDetail' => [
                /*'ParentCredential' => [
                    'Key' => $this->_data('parentkey'),
                    'Password' => $this->_data('parentpassword')
                ],*/
                'UserCredential' => [
                    'Key' => $this->_data('access_key'),
                    'Password' => $this->_data('password')
                ],
            ],
            'ClientDetail' => [
                'AccountNumber' => $this->_data('shipper_number'),
                'MeterNumber' => $this->_data('shipper_meter'),
            ],
            'Version' => [
                'ServiceId' => $this->_data('wsdl/ServiceId'),
                'Major' => $this->_data('wsdl/Major'),
                'Intermediate' => $this->_data('wsdl/Intermediate'),
                'Minor' => $this->_data('wsdl/Minor')
            ],
        ];

        return $request;
    }

    /**
     * Get missing data from wsdl file
     *
     * @param string $service
     * @param string $file
     */
    protected function _parseWsdl($service, $file)
    {
        if (empty(self::$_wsdlConfig[$service])) {
            $data = file_get_contents($file);
            $xml = new SimpleXMLElement($data);
            $xml->registerXPathNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
            foreach (self::$_wsdlMap as $field => $source) {
                /** @var SimpleXMLElement[] $element */
                $element = $xml->xpath($source['path']);
                if (is_array($element) && isset($element[0]) && isset($element[0][$source['attr']])) {
                    if (!isset($this->_requestData['wsdl'])) {
                        $this->_requestData['wsdl'] = [];
                    }

                    $this->_requestData['wsdl'][$field] = (string)$element[0][$source['attr']];
                }
            }
        }
    }

    public function getDropoffLocations()
    {
        return [
            'BUSINESS_SERVICE_CENTER' => $this->_('Authorized FedEx business service center'),
            'DROP_BOX' => $this->_('Drop box'),
            'REGULAR_PICKUP' => $this->_('Regular scheduled pickup'),
            'REQUEST_COURIER' => $this->_('Request a FedEx courier'),
            'STATION' => $this->_('FedEx Station'),
        ];
    }

    public function getServices()
    {
        return [
            '_INTERNATIONAL_PRIORITY' => 'International Priority',
            '_INTERNATIONAL_PRIORITY_SATURDAY_DELIVERY' => 'International Priority (Saturday Delivery)',
            '_INTERNATIONAL_ECONOMY' => 'International Economy',
            '_INTERNATIONAL_FIRST' => 'International First',
            '_INTERNATIONAL_PRIORITY_FREIGHT' => 'International Priority Freight',
            '_INTERNATIONAL_ECONOMY_FREIGHT' => 'International Economy Freight',
            '_INTERNATIONAL_GROUND' => 'International Ground',
            '_PRIORITY_OVERNIGHT' => 'Priority Overnight',
            '_PRIORITY_OVERNIGHT_SATURDAY_DELIVERY' => 'Priority Overnight (Saturday Delivery)',
            '_FEDEX_2_DAY' => '2Day',
            '_FEDEX_2_DAY_SATURDAY_DELIVERY' => '2Day (Saturday Delivery)',
            '_STANDARD_OVERNIGHT' => 'Standard Overnight',
            '_FIRST_OVERNIGHT' => 'First Overnight',
            '_FIRST_OVERNIGHT_SATURDAY_DELIVERY' => 'First Overnight (Saturday Delivery)',
            '_FEDEX_EXPRESS_SAVER' => 'Express Saver',
            '_FEDEX_1_DAY_FREIGHT' => '1 Day Freight',
            '_FEDEX_1_DAY_FREIGHT_SATURDAY_DELIVERY' => '1 Day Freight (Saturday Delivery)',
            '_FEDEX_2_DAY_FREIGHT' => '2 Day Freight',
            '_FEDEX_2_DAY_FREIGHT_SATURDAY_DELIVERY' => '2 Day Freight (Saturday Delivery)',
            '_FEDEX_3_DAY_FREIGHT' => '3 Day Freight',
            '_FEDEX_3_DAY_FREIGHT_SATURDAY_DELIVERY' => '3 Day Freight (Saturday Delivery)',
            '_GROUND_HOME_DELIVERY' => 'Ground Home Delivery',
            '_FEDEX_GROUND' => 'Ground',
        ];
    }


}