<?php

/**
 * Class Sellvana_ShippingUps_ShippingMethod
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property FCom_Core_Main $FCom_Core_Main
 * @property Sellvana_Sales_Model_Order_Shipment $Sellvana_Sales_Model_Order_Shipment
 * @property Sellvana_Sales_Model_Order_Shipment_Package $Sellvana_Sales_Model_Order_Shipment_Package
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_ShippingUps_ShippingMethod extends Sellvana_Sales_Method_Shipping_Abstract
{
    const SERVICE_SHIP = 'Ship';
    const SERVICE_TRACK = 'Track';

    protected $_name           = 'Universal post service';
    protected $_code           = 'ups';
    protected $_configPath     = 'modules/Sellvana_ShippingUps';
    protected $_trackingUpdate = true;

    /**
     * @var array
     */
    protected $_requestData = [];

    /**
     * Storage for SOAP clients
     *
     * @var array
     */
    protected $_clientCache = [];

    protected static $_statusMapping = [
        'D' => Sellvana_Sales_Model_Order_Shipment_Package_State_Overall::DELIVERED,
        'I' => Sellvana_Sales_Model_Order_Shipment_Package_State_Overall::IN_TRANSIT,
        'X' => Sellvana_Sales_Model_Order_Shipment_Package_State_Overall::EXCEPTION,
        'P' => Sellvana_Sales_Model_Order_Shipment_Package_State_Overall::RECEIVED,
        'M' => Sellvana_Sales_Model_Order_Shipment_Package_State_Overall::PENDING
    ];

    protected function _fetchRates($data)
    {
        $config = $this->BConfig->get('modules/Sellvana_ShippingUps');
        $data = array_merge($config, $data);

        $data = $this->_applyDefaultPackageConfig($data);

        if ($data['weight'] == 0) {
            $result = [
                'error' => 1,
                'message' => 'Can not ship without weight',
            ];
            return $result;
        }
        if (empty($data['from_postcode']) || empty($data['from_country'])) {
            $result = [
                'error' => 1,
                'message' => 'Origin Postcode and Country are required',
            ];
            return $result;
        }
        if (empty($data['to_postcode']) || empty($data['to_country'])) {
            $result = [
                'error' => 1,
                'message' => 'Destination zipcode and country are required',
            ];
            return $result;
        }
        if (empty($data['access_key']) || empty($data['user_id']) || empty($data['password'])) {
            $result = [
                'error' => 1,
                'message' => 'Incomplete UPS User Authentication configuration',
            ];
            return $result;
        }

        if (empty($data['rate_api_url'])) {
            $data['rate_api_url'] = 'https://wwwcie.ups.com/ups.app/xml/Rate';
            #$data['rate_api_url'] = 'https://onlinetools.ups.com/ups.app/xml/Rate';
        }
        if (empty($data['pickup_type'])) {
            $data['pickup_type'] = '01';
        }
        if (empty($data['packaging_type'])) {
            $data['packaging_type'] = '02';
        }
        if (empty($data['dimension_units'])) {
            $data['dimension_units'] = 'IN';
        }
        if (empty($data['weight_units'])) {
            $data['weight_units'] = 'LBS';
        }

        $request = "<?xml version=\"1.0\"?>
<AccessRequest xml:lang=\"en-US\">
    <AccessLicenseNumber>" . addslashes($data['access_key']) . "</AccessLicenseNumber>
    <UserId>" . addslashes($data['user_id']) . "</UserId>
    <Password>" . addslashes($data['password']) . "</Password>
</AccessRequest>
<?xml version=\"1.0\"?>
<RatingServiceSelectionRequest xml:lang=\"en-US\">
    <Request>
        <TransactionReference>
            <CustomerContext>" . addslashes($data['customer_context']) . "</CustomerContext>
            <XpciVersion>1.0001</XpciVersion>
        </TransactionReference>
        <RequestAction>Rate</RequestAction>
        <RequestOption>Shop</RequestOption>
    </Request>
    <PickupType>
        <Code>" . addslashes($data['pickup_type']) . "</Code>
    </PickupType>
    <Shipment>
        <Shipper>
            <Address>
                <PostalCode>" . addslashes($data['from_postcode']) . "</PostalCode>
                <CountryCode>" . addslashes($data['from_country']) . "</CountryCode>
            </Address>
            <ShipperNumber>" . addslashes($data['shipper_number']) . "</ShipperNumber>
        </Shipper>
        <ShipFrom>
            <Address>
                <PostalCode>" . addslashes($data['from_postcode']) . "</PostalCode>
                <CountryCode>" . addslashes($data['from_country']) . "</CountryCode>
            </Address>
        </ShipFrom>
        <ShipTo>
            <Address>
                <PostalCode>" . addslashes($data['to_postcode']) . "</PostalCode>
                <CountryCode>" . addslashes($data['to_country']) . "</CountryCode>
                " . ($data['residential'] ? "<ResidentialAddressIndicator/>" : '') . "
            </Address>
        </ShipTo>
        <Package>
            <PackagingType>
                <Code>" . addslashes($data['packaging_type']) . "</Code>
            </PackagingType>
            <Dimensions>
                <UnitOfMeasurement>
                    <Code>" . addslashes($data['dimension_units']) . "</Code>
                </UnitOfMeasurement>
                <Length>" . addslashes($data['length']) . "</Length>
                <Width>" . addslashes($data['width']) . "</Width>
                <Height>" . addslashes($data['height']) . "</Height>
            </Dimensions>
            <PackageWeight>
                <UnitOfMeasurement>
                    <Code>" . addslashes($data['weight_units']) . "</Code>
                </UnitOfMeasurement>
                <Weight>" . addslashes($data['weight']) . "</Weight>
            </PackageWeight>
        </Package>
    </Shipment>
</RatingServiceSelectionRequest>";

        $result = [];
        try {
            $response = $this->BUtil->remoteHttp('POST', $data['rate_api_url'], $request, [], ['timeout' => 2]);
            $parsed = new SimpleXMLElement($response);
        } catch (Exception $e) {
            $result['error'] = 1;
            $result['message'] = $this->_('Couldn\'t get response from UPS');

            return $result;
        }

        if ($parsed->Response->ResponseStatusCode == 1) { //success
            $result['success'] = 1;
            foreach ($parsed->RatedShipment as $rate) {
                $code = (string)$rate->Service->Code;
                $result['rates']['_' . $code] = [
                    'price' => (float)$rate->TotalCharges->MonetaryValue,
                    'max_days' => (int)$rate->GuaranteedDaysToDelivery,
                ];
            }
            $this->_lastError = null;
        } else { // error
            $result['error'] = 1;
            $result['message'] = $parsed->Response->Error->ErrorDescription;
            $this->_lastError = $result['message'];
        }
        return $result;
    }

    /**
     * UPS services
     * @return array
     */
    public function getServices()
    {
//        return [
//            '_01' => 'UPS Next Day Air',
//            '_02' => 'UPS Second Day Air',
//            '_03' => 'UPS Ground',
//            '_07' => 'UPS Worldwide Express',
//            '_08' => 'UPS Worldwide Expedited',
//            '_11' => 'UPS Standard',
//            '_12' => 'UPS Three-Day Select',
//            '_13' => 'Next Day Air Saver',
//            '_14' => 'UPS Next Day Air Early AM',
//            '_54' => 'UPS Worldwide Express Plus',
//            '_59' => 'UPS Second Day Air AM',
//            '_65' => 'UPS Saver',
//        ];

        //The commented Services are not available to return shipment
        return [
            '_01' => 'Next Day Air',
            '_02' => '2nd Day Air',
            '_03' => 'Ground',
            '_07' => 'Express',
            '_08' => 'Expedited',
            '_11' => 'UPS Standard',
            '_12' => '3 Day Select',
            '_13' => 'Next Day Air Saver',//
            '_14' => 'UPS Next Day Air Early',
            '_54' => 'Express Plus',
            '_59' => '2nd Day Air A.M.',//
            '_65' => 'UPS Saver',
            '_M2' => 'First Class Mail',
            '_M3' => 'Priority Mail',
            '_M4' => 'Expedited MaiI Innovations',
            '_M5' => 'Priority Mail Innovations',
            '_M6' => 'Economy Mail Innovations',
            '_70' => 'UPS Access Point Economy',
            '_82' => 'UPS Today Standard',//
            '_83' => 'UPS Today Dedicated Courier',//
            '_84' => 'UPS Today Intercity',//
            '_85' => 'UPS Today Express',//
            '_86' => 'UPS Today Express Saver',//
            '_96' => 'UPS Worldwide Express Freight',
        ];
    }

    /**
     * Send shipment confirmation to UPS
     *
     * @param Sellvana_Sales_Model_Order_Shipment $shipment
     * @throws BException
     */
    public function buyShipment(Sellvana_Sales_Model_Order_Shipment $shipment)
    {
        $cart = $shipment->order()->cart();
        $this->_requestData = array_merge($this->_requestData, $this->_getPackageTemplate($cart));
        $this->_requestData = array_merge($this->_requestData, $shipment->as_array());

        try {
            $client = $this->_getSoapClient(self::SERVICE_SHIP);
            $result = $client->__soapCall('ProcessShipment', array($this->_buildShipmentData($shipment)));
        } catch (SoapFault $e) {
            //$details = $e->detail;

            $message = $e->getMessage();
            if (isset($e->detail->Errors->ErrorDetail->PrimaryErrorCode->Description)) {
                $message .= ' ' . $e->detail->Errors->ErrorDetail->PrimaryErrorCode->Description;
            }
            throw new BException($message);
        }

        if ($result->Response->ResponseStatus->Code != 1) {
            $message = '(' . $result->Response->Allert->Code . ') ';
            $message .= $result->Response->Allert->Description;
            throw new BException($message);
        }

        $shipmentResults = $result->ShipmentResults;

        $trackingNumber = '';
        if (!empty($shipmentResults->PackageResults->TrackingNumber)) {
            // while multi-package shipments aren't implemented, we will receive only one tracking number
            $trackingNumber = $shipmentResults->PackageResults->TrackingNumber;
        }
        $this->_getFilesFromResponse($result, $shipment->packages());
        foreach ($shipment->packages() as $package) {
            $package->set('tracking_number', $trackingNumber);
            $package->setData('package_results', $shipmentResults->PackageResults);
            $package->save();
        }
        $shipment->setData('shipment_results', $shipmentResults);
        $shipment->setData('shipment_identification_number', $shipmentResults->ShipmentIdentificationNumber);
    }

    protected function _data($path, $default = null)
    {
        $result = $this->BUtil->dataGet($this->_requestData, $path);
        if (null === $result) {
            $result = $this->BConfig->get("modules/Sellvana_ShippingUps/{$path}");
        }
        if (null === $result) {
            $result = $default;
        }
        return $result;
    }

    protected function _buildShipmentData(Sellvana_Sales_Model_Order_Shipment $shipment = null)
    {
        $config = $this->BConfig;
        $services = $this->getServices();
        $catalogConfig = $this->BConfig->get('modules/Sellvana_Catalog');
        $dimensions = explode('x', $this->_data('package_size'));
        $weightCode = [
            'lb' => 'LBS',
            'kg' => 'KGS'
        ];
        $weight = $this->_data('weight') ?: $this->_data('shipping_weight');
        if (count($dimensions) !== 3) {
            throw new BException('Dimensions in wrong format');
        }
        $labelFormat = $this->getLabelFormats();
        $labelFormat = $labelFormat[$this->_data('shipping_label_format')];

        $request = [];
        $request['Request'] = [
            'RequestOption' => 'nonvalidate',
        ];

        $shipmentSection = [];
        $shipmentSection['Description'] = 'Ship WS test';

        $shipmentSection['Shipper'] = [
            'Name' => $config->get("modules/Sellvana_Sales/store_name"),
            'AttentionName' => $config->get("modules/Sellvana_Sales/store_name"), //Required for: see doc.
            //'TaxIdentificationNumber' => '123456', //Required for: see doc.
            'ShipperNumber' => $this->_data('shipper_number'),
            'Address' => [
                'AddressLine' => [
                    $config->get("modules/Sellvana_Sales/store_street1"),
                    $config->get("modules/Sellvana_Sales/store_street2"),
                ],
                'City' => $config->get("modules/Sellvana_Sales/store_city"),
                'StateProvinceCode' => $config->get("modules/Sellvana_Sales/store_region"),
                'PostalCode' => $config->get("modules/Sellvana_Sales/store_postcode"),
                'CountryCode' => $config->get("modules/Sellvana_Sales/store_country"),
            ],
            'Phone' => [
                'Number' => $config->get("modules/Sellvana_Sales/store_phone"),
                //'Extension' => '1',
            ],
        ];

        $shipmentSection['ShipTo'] = [
            'Name' => $this->_data('to_name'),
            'AttentionName' => $this->_data('to_name'),
            'Address' => [
                'AddressLine' => [
                    $this->_data('to_street1'),
                    $this->_data('to_street2'),
                ],
                'City' => $this->_data('to_city'),
                'StateProvinceCode' => substr($this->_data('to_region'), 0, 2),
                'PostalCode' => $this->_data('to_postcode'),
                'CountryCode' => $this->_data('to_country'),
            ],
            'Phone' => [
                'Number' => $this->_data('to_phone'),
            ],
        ];

        $shipmentSection['ShipFrom'] = [
            'Name' => $this->BConfig->get("modules/Sellvana_Sales/store_name"),
            'AttentionName' => $this->BConfig->get("modules/Sellvana_Sales/store_name"),
            'Address' => [
                'AddressLine' => [
                    $this->BConfig->get("modules/Sellvana_Sales/store_street1"),
                    $this->BConfig->get("modules/Sellvana_Sales/store_street2"),
                ],
                'City' => $this->BConfig->get("modules/Sellvana_Sales/store_city"),
                'StateProvinceCode' => $this->BConfig->get("modules/Sellvana_Sales/store_region"),
                'PostalCode' => $this->BConfig->get("modules/Sellvana_Sales/store_postcode"),
                'CountryCode' => $this->BConfig->get("modules/Sellvana_Sales/store_country"),
            ],
            'Phone' => [
                'Number' => $this->BConfig->get("modules/Sellvana_Sales/store_phone"),
            ],
        ];

        $shipmentSection['Service'] = [
            'Code' => trim($shipment->get('service_code'), '_'),
            'Description' => $services[$shipment->get('service_code')],
        ];

        $shipmentSection['Package'] = [
            'Description' => '',
            'Packaging' => [
                'Code' => '02',
            ],
            'Dimensions' => [
                'UnitOfMeasurement' => [
                    'Code' => strtoupper($catalogConfig['length_unit']),
                ],
                'Length' => $dimensions[0],
                'Width' => $dimensions[1],
                'Height' => $dimensions[2],
            ],
            'PackageWeight' => [
                'UnitOfMeasurement' => [
                    'Code' => $weightCode[$catalogConfig['weight_unit']],
                ],
                'Weight' => $weight,
            ],
        ];

        $shipmentSection['LabelSpecification'] = [
            'LabelImageFormat' => [
                'Code' => $labelFormat,
            ],
            'HTTPUserAgent' => 'Mozilla/4.5',
        ];

        $shipmentSection['PaymentInformation'] = [
            'ShipmentCharge' => [
                'Type' => '01',
                'BillShipper' => [
                    'AccountNumber' => $this->_data('shipper_number'),
                ],
            ],
        ];

        $request['Shipment'] = $shipmentSection;

        return $request;
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

        $modRootDir = $this->BModuleRegistry->module('Sellvana_ShippingUps')->root_dir;
        $wsdl = "{$modRootDir}/schema-wsdls/{$service}/{$service}.wsdl";

        ini_set('soap.wsdl_cache_enabled', '0');

        $soapParams = [
            'soap_version' => 'SOAP_1_1',  // use soap 1.1 client
            'trace' => 1,
        ];

        $client = new SoapClient($wsdl, $soapParams);

        $UPSSecurity = [
            'UsernameToken' => [
                'Username' => $this->_data('user_id'),
                'Password' => $this->_data('password'),
            ],
            'ServiceAccessToken' => [
                'AccessLicenseNumber' => $this->_data('access_key'),
            ],
        ];

        $header = new SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0', 'UPSSecurity', $UPSSecurity);
        $client->__setSoapHeaders($header);

        $this->_clientCache[$serviceKey] = $client;

        return $this->_clientCache[$serviceKey];
    }

    /**
     * @param Sellvana_Sales_Model_Order_Shipment_Package $package
     * @return bool|string
     */
    public function getPackageLabel(Sellvana_Sales_Model_Order_Shipment_Package $package)
    {
        $data = $package->getData('package_results');
        $fileName = $data['ShippingLabel']['GraphicImage'];
        if ($fileName) {
            /** @var Sellvana_Sales_Model_Order_Shipment $shipment */
            $label = [
                'content' => $package->getFileContent($fileName),
                'filename' => $fileName
            ];

            return $label;
        }

        return false;
    }

    /**
     * @param stdClass $response
     * @param Sellvana_Sales_Model_Order_Shipment_Package[] $packages
     */
    protected function _getFilesFromResponse(stdClass $response, array $packages)
    {
        $files = [
            'label',
            'label_html',
            'requested_international_forms'
        ];

        $shippingLabel = $response->ShipmentResults->PackageResults->ShippingLabel;
        foreach ($files as $file) {
            $fileName = null;
            $fileContent = null;
            $responseContent = null;

            switch ($file) {
                case 'label':
                    $fileExtension = strtolower($shippingLabel->ImageFormat->Code);
                    $responseContent = $shippingLabel->GraphicImage;
                    $fileName = 'label.' . $fileExtension;
                    $shippingLabel->GraphicImage = $fileName;
                    break;
                case 'label_html':
                    $responseContent = $shippingLabel->HTMLImage;
                    $fileName = 'label.html';
                    $shippingLabel->HTMLImage = $fileName;
                    break;
                case 'requested_international_forms':
                    if (isset($response->ShipmentResults->Form)) {
                        $form = $response->ShipmentResults->Form;
                        $fileExtension = strtolower($form->Image->ImageFormat->Code);
                        $responseContent = $form->Image->GraphicImage;
                        $fileName = 'form.' . $fileExtension;
                        $form->Image->GraphicImage = $fileName;
                    }
                    break;
            }

            $fileContent = base64_decode($responseContent);
            if ($fileName && $fileContent) {
                foreach ($packages as $package) {
                    $package->putFile($fileName, $fileContent);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getLabelFormats()
    {
        return [
            '_01' => 'EPL',
            '_02' => 'SPL',
            '_03' => 'ZPL',
            '_04' => 'GIF'
        ];
    }

    /**
     * @inheritdoc;
     */
    public function fetchTrackingUpdates($args)
    {
        $states = [];

        try {
            foreach ($args as $packageId => $package) {
                $trackingNumber = $package->get('tracking_number');

                $order = $order = $this->Sellvana_Sales_Model_Order->load($package->get('order_id'));
                if (!$order) {
                    throw new BException('Invalid order');
                }

                $shipment = $this->Sellvana_Sales_Model_Order_Shipment->load($package->get('shipment_id'));
                if (!$shipment) {
                    throw new BException('Invalid shipment');
                }

                $request['tracking_number'] = $trackingNumber;
                $request['shipment_identification_number'] = $shipment->getData('shipment_identification_number');

                //<-- Develop section
                //$str = '1Z12345E0205271688';
                //$str = '1Z12345E6605272234';
                //$str = '1Z12345E0305271640';
                //$str = '1Z12345E0393657226';
                //$str = '1Z12345E1305277940';
                //$str = '1Z12345E6205277936';
                //$str = '1Z12345E020527079';
                //$str = '1Z12345E1505270452';
                //$str = '990728071';
                //$str = '3251026119';
                //$str = '9102084383041101186729';
                //$str = 'cgish000116630';
                //$str = '1Z648616E192760718';
                //$str = '5548789114';
                //$str = 'ER751105042015062';
                //$str = '1ZWX0692YP40636269';

                //$request['tracking_number'] = $str;
                //$request['shipment_identification_number'] = $str;
                //Develop section -->

                $states[$packageId] = $this->_fetchNewStates($request, $package);
            }
        } catch (Exception $e) {
            return ['error' => true, 'message' => $e->getMessage(), 'states' => $states];
        }

        return ['message' => 'Success', 'states' => $states];
    }

    /**
     * @param array $data
     * @param Sellvana_Sales_Model_Order_Shipment_Package $package
     * @return false|array
     * @throws BException
     */
    protected function _fetchNewStates($data, Sellvana_Sales_Model_Order_Shipment_Package $package)
    {
        try {
            $client = $this->_getSoapClient(self::SERVICE_TRACK);
            $request = [
                'Request' => [
                    'RequestOption' => '1',
                    'TransactionReference' => [
                        'CustomerContext' => 'Request of ' . $data['tracking_number']
                    ],
                ],
                'TrackingNumber' => $data['tracking_number'],
                'InquiryNumber' => $data['shipment_identification_number'],
            ];
            $result = $client->__soapCall('ProcessTrack', array($request));
        } catch (SoapFault $e) {
            $message = $e->getMessage();
            if (property_exists($e, 'detail')) {
                $message = $e->detail->Errors->ErrorDetail->PrimaryErrorCode->Description;
            }
            throw new BException($message);
        }

        if (!property_exists($result->Shipment, 'Package')){
            throw new BException('Not implemented');
        }

        $activity = [];
        $packageData = $result->Shipment->Package;
        if (is_array($packageData)) {
            foreach ($packageData as $pkg) {
                if ($pkg->TrackingNumber == $data['tracking_number']) {
                    $packageData = $pkg;
                    break;
                }
            }
        }
        $rActivity = $packageData->Activity;
        if (!is_array($rActivity)){
            $rActivity = [];
            $rActivity[] = $packageData->Activity;
        }
        foreach ($rActivity as $item) {
            $key = $item->Date . $item->Time;
            $activity[$key] = $item->Status;
        }
        krsort($activity);
        $package->setData('tracking_updates', $activity);
        $latestActivity = reset($activity);
        $package->set('carrier_status', $latestActivity->Description);
        $upsStatus = isset($latestActivity->Type) ? $latestActivity->Type : $latestActivity->Description;
        if (array_key_exists($upsStatus, self::$_statusMapping)) {
            return [self::$_statusMapping[$upsStatus] => $latestActivity->Description];
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function getTrackingUrl(Sellvana_Sales_Model_Order_Shipment_Package $package)
    {
        if (!$package->get('tracking_number')) {
            return false;
        }

        return 'https://wwwapps.ups.com/tracking/tracking.cgi?tracknum=' . $package->get('tracking_number');
    }
}
