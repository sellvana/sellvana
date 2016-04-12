<?php

/**
 * Class Sellvana_ShippingUps_ShippingMethod
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_ShippingUps_ShippingMethod extends Sellvana_Sales_Method_Shipping_Abstract
{
    const SERVICE_SHIP = 'Ship';
    
    protected $_name       = 'Universal post service';
    protected $_code       = 'ups';
    protected $_configPath = 'modules/Sellvana_ShippingUps';

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

        $request ="<?xml version=\"1.0\"?>
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

        $response = $this->BUtil->remoteHttp('POST', $data['rate_api_url'], $request, [], ['timeout' => 2]);

        $parsed = new SimpleXMLElement($response);

        $result = [];
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
        return [
            '_01' => 'UPS Next Day Air',
            '_02' => 'UPS Second Day Air',
            '_03' => 'UPS Ground',
            '_07' => 'UPS Worldwide Express',
            '_08' => 'UPS Worldwide Expedited',
            '_11' => 'UPS Standard',
            '_12' => 'UPS Three-Day Select',
            '_13' => 'Next Day Air Saver',
            '_14' => 'UPS Next Day Air Early AM',
            '_54' => 'UPS Worldwide Express Plus',
            '_59' => 'UPS Second Day Air AM',
            '_65' => 'UPS Saver',
        ];
    }

    /**
     * @param Sellvana_Sales_Model_Order_Shipment $shipment
     */
    public function buyShipment(Sellvana_Sales_Model_Order_Shipment $shipment)
    {
        $cart = $shipment->order()->cart();
        $this->_requestData = array_merge($this->_requestData, $this->_getPackageTemplate($cart));
        $this->_requestData = array_merge($this->_requestData, $shipment->as_array());

        try
        {
            $client = $this->_getSoapClient(self::SERVICE_SHIP);

            //get response
            $result = $client->__soapCall('ProcessShipment', array($this->_buildShipmentData()));
            var_dump($result->Response->ResponseStatus->Code);
            exit();

            //save soap request and response to file
            $outputFileName = $this->BConfig->get('fs/log_dir') . '/' . "XOLTResult.xml";
            $fw = fopen($outputFileName, 'w');
            fwrite($fw, "Request: \n" . $client->__getLastRequest() . "\n");
            fwrite($fw, "Response: \n" . $client->__getLastResponse() . "\n");
            fclose($fw);
            
        }
        catch(Exception $e)
        {
//            var_dump($e);
            var_dump($e->getMessage());
            var_dump($e->detail);
            exit();
        }
        exit();
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
        return [
            'Request' => [
                'RequestOption' => 'nonvalidate',
            ],
            'Shipment' => [
                'Description' => 'Ship WS test',
                'Shipper' => [
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
                ],
                'ShipTo' => [
                    'Name' => 'Happy Dog Pet Supply',
                    'AttentionName' => 'Ship To Attention Name',
                    'Address' => [
                        'AddressLine' => 'GOERLITZER STR.1',
                        'City' => 'Neuss',
                        'PostalCode' => '41456',
                        'CountryCode' => 'DE',
                    ],
                    'Phone' => [
                        'Number' => '9225377171',
                    ],
                ],
                'ShipFrom' => [
                    'Name' => 'T and T Designs',
                    'AttentionName' => '1160b_74',
                    'Address' => [
                        'AddressLine' => '2311 York Rd',
                        'City' => 'Timonium',
                        'StateProvinceCode' => 'MD',
                        'PostalCode' => '21093',
                        'CountryCode' => 'US',
                    ],
                    'Phone' => [
                        'Number' => '1234567890',
                    ],
                ],
                'PaymentInformation' => [
                    'ShipmentCharge' => [
                        'Type' => '01',
                        'BillShipper' => [
                            'CreditCard' => [
                                'Type' => '06',
                                'Number' => '4716995287640625',
                                'SecurityCode' => '864',
                                'ExpirationDate' => '12/2013',
                                'Address' => [
                                    'AddressLine' => '2010 warsaw road',
                                    'City' => 'Roswell',
                                    'StateProvinceCode' => 'GA',
                                    'PostalCode' => '30076',
                                    'CountryCode' => 'US',
                                ],
                            ],
                        ],
                    ],
                ],
                'Service' => [
                    'Code' => '08',
                    'Description' => 'Expedited',
                ],
                'ShipmentServiceOptions' => [
                    'InternationalForms' => [
                        'FormType' => '01',
                        'InvoiceNumber' => 'asdf123',
                        'InvoiceDate' => '20151225',
                        'PurchaseOrderNumber' => '999jjj777',
                        'TermsOfShipment' => 'CFR',
                        'ReasonForExport' => 'Sale',
                        'Comments' => 'Your Comments',
                        'DeclarationStatement' => 'Your Declaration Statement',
                        'Contacts' => [
                            'SoldTo' => [
                                'Option' => '01',
                                'AttentionName' => 'Sold To Attn Name',
                                'Name' => 'Sold To Name',
                                'Phone' => [
                                    'Number' => '1234567890',
                                    'Extension' => '1234',
                                ],
                                'Address' => [
                                    'AddressLine' => '34 Queen St',
                                    'City' => 'Frankfurt',
                                    'PostalCode' => '60547',
                                    'CountryCode' => 'DE',
                                ],
                            ],
                        ],
                        'Product' => [
                            'Description' => 'Product 1',
                            'CommodityCode' => '111222AA',
                            'OriginCountryCode' => 'US',
                            'Unit' => [
                                'Number' => '147',
                                'Value' => '478',
                                'UnitOfMeasurement' =>
                                    [
                                        'Code' => 'BOX',
                                        'Description' => 'BOX',
                                    ],
                            ],
                            'ProductWeight' => [
                                'Weight' => '10',
                                'UnitOfMeasurement' =>
                                    [
                                        'Code' => 'LBS',
                                        'Description' => 'LBS',
                                    ],
                            ],
                        ],
                        'Discount' => [
                            'MonetaryValue' => '100',
                        ],
                        'FreightCharges' => [
                            'MonetaryValue' => '50',
                        ],
                        'InsuranceCharges' => [
                            'MonetaryValue' => '200',
                        ],
                        'OtherCharges' => [
                            'MonetaryValue' => '50',
                            'Description' => 'Misc',
                        ],
                        'CurrencyCode' => 'USD',
                    ],
                ],
                'Package' => [
                    'Description' => '',
                    'Packaging' => [
                        'Code' => '02',
                        'Description' => 'Nails',
                    ],
                    'Dimensions' => [
                        'UnitOfMeasurement' =>
                            [
                                'Code' => 'IN',
                                'Description' => 'Inches',
                            ],
                        'Length' => '7',
                        'Width' => '5',
                        'Height' => '2',
                    ],
                    'PackageWeight' => [
                        'UnitOfMeasurement' =>
                            [
                                'Code' => 'LBS',
                                'Description' => 'Pounds',
                            ],
                        'Weight' => '10',
                    ],
                ],
                'LabelSpecification' => [
                    'LabelImageFormat' => [
                        'Code' => 'GIF',
                        'Description' => 'GIF',
                    ],
                    'HTTPUserAgent' => 'Mozilla/4.5',
                ],
            ],
        ];
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
                'Password' => 'a'//$this->_data('password'),
            ],
            'ServiceAccessToken' => [
                'AccessLicenseNumber' => $this->_data('access_key'),
            ],
        ];

        $header = new SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0','UPSSecurity',$UPSSecurity);
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
        $logDir = $this->BConfig->get('fs/log_dir');
        $outputFileName = $logDir . '/' . "XOLTResult.xml";
        $fw = fopen($outputFileName , 'r');
        $line = false;
        while (!feof($fw)){
            $line = fgets($fw);
            $line = trim($line);
            if (strcmp($line, 'Response:') == 0) {
                $line = fgets($fw);
                fclose($fw);
                break;
            }
            $line = false;
        }

        if ($line) {
            $xml = simplexml_load_string($line, NULL, NULL, "http://schemas.xmlsoap.org/soap/envelope/");

            $xml->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
            return (base64_decode((string)$xml->Body->children('ship', true)->ShipmentResponse->ShipmentResults->PackageResults->ShippingLabel->GraphicImage));
        }

        return false;
    }
}
