<?php

/**
 * Class Sellvana_ShippingUps_ShippingMethod
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_ShippingUps_ShippingMethod extends Sellvana_Sales_Method_Shipping_Abstract
{
    protected $_name = 'Universal post service';
    protected $_code = 'ups';
    protected $_configPath = 'modules/Sellvana_ShippingUps';

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
#echo "<xmp>"; print_r($request); print_r($response); echo "</xmp>"; exit;
        //echo '<!-- '. $response. ' -->'; // THIS LINE IS FOR DEBUG PURPOSES ONLY-IT WILL SHOW IN HTML COMMENTS
if (strpos($response, '<') !== 0) {
    echo "<xmp>"; echo $response; echo "</xmp>"; exit;
}
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
        //echo "<pre>"; var_dump($result); exit;
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

    public function createShipment($data)
    {

        //Configuration
        $access         = " Add License Key Here";
        $userid         = " Add User Id Here";
        $passwd         = " Add Password Here";
        $wsdl           = " Add Wsdl File Here ";
        $operation      = "ProcessShipment";
        $endpointurl    = ' Add URL Here';
        $outputFileName = "XOLTResult.xml";

//        function processShipment()
//        {
        //create soap request
        $request['Request']            = array
        (
            'RequestOption' => array('1', 'Shipping')
        );
        $shipfrom['Name']              = 'Pat Stewart';
        $shipfrom['TaxIdentification'] = '1234567890';
        $shipfrom['Address']           = array
        (
            'AddressLine' => '2311 York Road',
            'City' => 'Timonium',
            'StateProvinceCode' => 'MD',
            'PostalCode' => '21093',
            'CountryCode' => 'US'
        );
        $shipfrom['AttentionName']     = 'String';
        $shipfrom['Phone']             = array
        (
            'Number' => '6785851000',
            'Extension' => '123'
        );
        $shipment['ShipFrom']          = $shipfrom;
        $shipment['ShipperNumber']     = 'Your Shipper Number';

        $shipto['Name']          = 'Superman';
        $shipto['Address']       = array
        (
            'AddressLine' => '2010 Warsaw Road',
            'StateProvinceCode' => 'ON',
            'PostalCode' => 'M4C2M6',
            'CountryCode' => 'CA',

            'City' => 'Roswell',
            'StateProvinceCode' => 'GA',
            'PostalCode' => '30076',
            'CountryCode' => 'US'
        );
        $shipto['AttentionName'] = 'String';
        $shipto['Phone']         = array
        (
            'Number' => '6785851000',
            'Extention' => '111'
        );
        $shipment['ShipTo']      = $shipto;

        $payer['Name']                               = 'Superman';
        $payer['Address']                            = array
        (
            'AddressLine' => '2010 Warsaw Road',
            'City' => 'Roswell',
            'StateProvinceCode' => 'GA',
            'PostalCode' => '30075',
            'CountryCode' => 'US'
        );
        $payer['ShipperNumber']                      = 'Your Shipper Number';
        $payer['AttentionName']                      = 'String';
        $payer['Phone']                              = array
        (
            'Number' => '6785851000'
        );
        $paymentinformation['Payer']                 = $payer;
        $paymentinformation['ShipmentBillingOption'] = array
        (
            'Code' => '10',
            'Description' => 'String'
        );
        $shipment['PaymentInformation']              = $paymentinformation;
        $shipment['Service']                         = array
        (
            'Code' => '308',
            'Description' => 'String'
        );

        $shipment['HandlingUnitOne'] = array
        (
            'Quantity' => '16',
            'Type' => array
            (
                'Code' => 'PLT',
                'Description' => 'String'
            )
        );
        $shipment['Commodity']       = array
        (
            'CommodityID' => '22',
            'Description' => 'BUGS',
            'Weight' => array
            (
                'UnitOfMeasurement' => array
                (
                    'Code' => 'LBS',
                    'Description' => 'String'
                ),
                'Value' => '511.25'
            ),
            'Dimensions' => array
            (
                'UnitOfMeasurement' => array
                (
                    'Code' => 'IN',
                    'Description' => 'String'
                ),
                'Length' => '1.25',
                'Width' => '1.2',
                'Height' => '5'
            ),
            'NumberOfPieces' => '1',
            'PackagingType' => array
            (
                'Code' => 'PLT',
                'Description' => 'String'
            ),
            'CommodityValue' => array
            (
                'CurrencyCode' => 'USD',
                'MonetaryValue' => '265.2'
            ),
            'FreightClass' => '60',
            'NMFCCommodityCode' => '566'
        );

        $shipment['Reference'] = array
        (
            'Number' => array
            (
                'Code' => 'PM',
                'Value' => '1651651616'
            ),

            'BarCodeIndicator' => array
            (
                'NumberOfCartons' => '5',
                'Weight' => array
                (
                    'UnitOfMeasurement' => array
                    (
                        'Code' => 'LBS',
                        'Description' => 'String'
                    ),
                    'Value' => '2'
                )
            )
        );
        $request['Shipment']   = $shipment;

        echo "Request.......\n";
        print_r($request);
        echo "\n\n";
        #return $request;
//        }

        try {

            $mode = array
            (
                'soap_version' => 'SOAP_1_1',  // use soap 1.1 client
                'trace' => 1
            );

            // initialize soap client
            $client = new SoapClient($wsdl, $mode);

            //set endpoint url
            $client->__setLocation($endpointurl);


            //create soap header
            $usernameToken['Username']                   = $userid;
            $usernameToken['Password']                   = $passwd;
            $serviceAccessLicense['AccessLicenseNumber'] = $access;
            $upss['UsernameToken']                       = $usernameToken;
            $upss['ServiceAccessToken']                  = $serviceAccessLicense;

            $header = new SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0', 'UPSSecurity', $upss);
            $client->__setSoapHeaders($header);


            //get response
            @ $resp = $client->__soapCall($operation, array(processShipment()));

            //get status
            echo "Response Status: " . $resp->Response->ResponseStatus->Description . "\n";

        }
        catch(Exception $ex)
        {
            print_r ($ex);
        }
    }
}
