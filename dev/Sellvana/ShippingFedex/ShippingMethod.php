<?php

/**
 * Class Sellvana_ShippingFedex_ShippingMethod
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_ShippingFedex_ShippingMethod extends Sellvana_Sales_Method_Shipping_Abstract
{
    const SERVICE_RATE = 'Rate';
    const SERVICE_SHIP = 'Ship';

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
        $request = array_merge($request, $this->_buildShipmentData());

        $rates = $rateClient->getRates($request);
        $this->BDebug->log(print_r($rates, 1), 'fedex.log');

        if ($rates->HighestSeverity == 'ERROR') {
            $message = '';
            $notifications = $rates->Notifications;

            if (!is_array($notifications)) {
                $notifications = [$notifications];
            }

            foreach ($notifications as $notification) {
                $curMessage = empty($notification->LocalizedMessage) ? $notification->LocalizedMessage : $notification->Message;
                $message .= $curMessage . "<br>";
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

        $rateReplyDetails = $rates->RateReplyDetails;
        if (!is_array($rateReplyDetails)) {
            $rateReplyDetails = [$rateReplyDetails];
        }

        foreach ($rateReplyDetails as $service) {
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
     * Send shipment confirmation to FedEx
     *
     * @param Sellvana_Sales_Model_Order_Shipment $shipment
     * @throws BException
     */
    public function buyShipment(Sellvana_Sales_Model_Order_Shipment $shipment)
    {
        $cart = $shipment->order()->cart();
        $this->_requestData = array_merge($this->_requestData, $this->_getPackageTemplate($cart));
        $this->_requestData = array_merge($this->_requestData, $shipment->as_array());
        $client = $this->_getSoapClient(self::SERVICE_SHIP);
        $request = $this->_buildRequest();
        $request = array_merge($request, $this->_buildShipmentData($shipment));
        $request['RequestedShipment']['ServiceType'] = trim($shipment->get('service_code'), '_');
        $request['TransactionDetail'] = ['CustomerTransactionId' => 'Process shipment request for order ID: ' . $shipment->order()->id()];
        $result = $client->processShipment($request);

        if ($result->HighestSeverity == 'ERROR') {
            $message = '';
            $notifications = $result->Notifications;
            if (!is_array($notifications)) {
                $notifications = [$notifications];
            }

            foreach ($notifications as $notification) {
                $message .= empty($notification->LocalizedMessage) ? $notification->LocalizedMessage : $notification->Message;
            }

            throw new BException($message);
        }

        $shipmentDetail = $result->CompletedShipmentDetail;
        $trackingNumber = '';
        if (!empty($shipmentDetail->CompletedPackageDetails->TrackingIds)) {
            // while multi-package shipments aren't implemented, we will receive only one tracking number
            $trackingNumber = $shipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber;
        }
        foreach ($shipment->packages() as $package) {
            $package->set('tracking_number', $trackingNumber);
            $package->setData('completed_package_details', $shipmentDetail->CompletedPackageDetails);
            $package->save();
        }
        $shipment->setData('completed_shipment_detail', $shipmentDetail);
        $shipment->setData('job_id', $result->JobId);
    }

    /**
     * @param Sellvana_Sales_Model_Order_Shipment $shipment
     * @throws BException
     */
    public function cancelShipment(Sellvana_Sales_Model_Order_Shipment $shipment)
    {
        $cart = $shipment->order()->cart();
        $this->_requestData = array_merge($this->_requestData, $this->_getPackageTemplate($cart));
        $this->_requestData = array_merge($this->_requestData, $shipment->as_array());
        $client = $this->_getSoapClient(self::SERVICE_SHIP);
        $request = $this->_buildRequest();
        $shipmentDetail = $shipment->getData('completed_shipment_detail');
        if (!empty($shipmentDetail['CompletedPackageDetails']['TrackingIds'])) {
            $trackingId = $shipmentDetail['CompletedPackageDetails']['TrackingIds'];
            $request['TrackingId'] = [
                'TrackingIdType' => $trackingId['TrackingIdType'],
                'TrackingNumber' => $trackingId['TrackingNumber'],
            ];
        }
        $request['DeletionControl'] = 'DELETE_ALL_PACKAGES';
        $result = $client->deleteShipment($request);

        if ($result->HighestSeverity == 'ERROR') {
            $message = '';
            $notifications = $result->Notifications;
            if (!is_array($notifications)) {
                $notifications = [$notifications];
            }

            foreach ($notifications as $notification) {
                $message .= empty($notification->LocalizedMessage) ? $notification->LocalizedMessage : $notification->Message;
            }

            throw new BException($message);
        }
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

    protected function _buildShipmentData(Sellvana_Sales_Model_Order_Shipment $shipment = null)
    {
        $catalogConfig = $this->BConfig->get('modules/Sellvana_Catalog');
        $dimensions = explode('x', $this->_data('package_size'));
        if (count($dimensions) !== 3) {
            $result = [
                'error' => 1,
                'message' => 'Dimensions in wrong format',
            ];
            return $result;
        }

        $weight = $this->_data('weight') ?: $this->_data('shipping_weight');
        $request = [
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
                        'PersonName' => $this->_data('to_name'),
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
                    'ImageType' => 'PDF',
                    'LabelStockType' => 'PAPER_7X4.75',
                ],
                'PackageCount' => 1,
                'PackageDetail' => 'INDIVIDUAL_PACKAGES',
                'RequestedPackageLineItems' => [
                    'SequenceNumber' => 1,
                    'GroupPackageCount' => 1,
                    'Weight' => [
                        'Value' => $weight,
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
        ];

        if ($shipment && $this->BConfig->get("modules/Sellvana_Sales/store_country") !== ('to_country')) {
            $request['RequestedShipment']['CustomsClearanceDetail'] = [
                'DutiesPayment' => [
                    'PaymentType' => 'SENDER',
                    'Payor' => [
                        'ResponsibleParty' => [
                            'AccountNumber' => $this->_data('shipper_number'),
                            'Contact' => null,
                            'Address' => [
                                'CountryCode' => $this->BConfig->get("modules/Sellvana_Sales/store_country")
                            ],
                        ],
                    ]
                ],
                'DocumentContent' => 'NON_DOCUMENTS',
                'CustomsValue' => [
                    'Amount' => $this->_data('amount'),
                    'Currency' => $this->BConfig->get('modules/FCom_Core/base_currency'),
                ],
                'Commodities' => [],
                'ExportDetail' => [
                    'B13AFilingOption' => 'NOT_REQUIRED'
                ],
            ];

            $oItems = $shipment->order()->items();
            foreach ($shipment->items() as $item) {
                /** @var false|Sellvana_Catalog_Model_Product $product */
                $product = false;
                foreach ($oItems as $oItem) {
                    if ($oItem->id() == $item->get('order_item_id')) {
                        $product = $oItem->product();
                        break;
                    }
                }

                if (!$product || !isset($oItem)) {
                    throw new BException('Product for order item with ID ' . $item->get('order_item_id') . ' does not exist');
                }

                $inventory = $product->getInventoryModel();
                $request['RequestedShipment']['CustomsClearanceDetail']['Commodities'][] = [
                    'NumberOfPieces' => 1,
                    'Description' => $product->getName(),
                    'CountryOfManufacture' => $inventory->get('origin_country'),
                    'Weight' => [
                        'Units' => strtoupper($catalogConfig['weight_unit']),
                        'Value' => $inventory->get('shipping_weight')
                    ],
                    'Quantity' => ceil((float)$item->get('qty')),
                    'QuantityUnits' => 'pcs',
                    'UnitPrice' => [
                        'Currency' => $this->BConfig->get('modules/FCom_Core/base_currency'),
                        'Amount' =>  $oItem->get('price')
                    ],
                    'CustomsValue' => [
                        'Currency' => $this->BConfig->get('modules/FCom_Core/base_currency'),
                        'Amount' =>  $oItem->get('row_total')
                    ]
                ];
            }
        }

        if ($shipment && $this->_data('insurance')) {
            $request['TotalInsuredValue'] = [
                'Amount' => $shipment->order()->get('subtotal'),
                'Currency' => $this->BConfig->get('modules/FCom_Core/base_currency'),
            ];
        }

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

    /**
     * @param Sellvana_Sales_Model_Order_Shipment_Package $package
     * @return bool|string
     */
    public function getPackageLabel(Sellvana_Sales_Model_Order_Shipment_Package $package)
    {
        $data = $package->getData('completed_package_details');
        if (!empty($data)) {
            return $data['Label']['Parts']['Image'];
        }

        return false;
    }


}