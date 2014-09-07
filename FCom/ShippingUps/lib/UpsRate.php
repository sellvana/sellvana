<?php defined('BUCKYBALL_ROOT_DIR') || die();

class UpsRate {
    protected $AccessLicenseNumber;
    protected $UserId;
    protected $Password;
    protected $shipperNumber;
    protected $credentials;
    protected $rateApiUrl = 'https://www.ups.com/ups.app/xml/Rate';

    protected $response;

    public function __construct($rateApiUrl='')
    {
        if (!empty($rateApiUrl)) {
            $this->rateApiUrl = $rateApiUrl;
        }
    }
    public function setUpsParams($access,$user,$pass,$shipper)
    {
	$this->AccessLicenseNumber = $access;
	$this->UserID = $user;
	$this->Password = $pass;
	$this->shipperNumber = $shipper;
	$this->credentials = 1;
    }

    // Define the function getRate() - no parameters
    public function getRate($fromZip,$toZip,$service,$length,$width,$height,$weight)
    {
	if ($this->credentials != 1) {
		print 'Please set your credentials with the setCredentials function';
		die();
	}
	$data ="<?xml version=\"1.0\"?>
		<AccessRequest xml:lang=\"en-US\">
		    <AccessLicenseNumber>$this->AccessLicenseNumber</AccessLicenseNumber>
		    <UserId>$this->UserID</UserId>
		    <Password>$this->Password</Password>
		</AccessRequest>
		<?xml version=\"1.0\"?>
		<RatingServiceSelectionRequest xml:lang=\"en-US\">
		    <Request>
			<TransactionReference>
			    <CustomerContext>Bare Bones Rate Request</CustomerContext>
			    <XpciVersion>1.0001</XpciVersion>
			</TransactionReference>
			<RequestAction>Rate</RequestAction>
			<RequestOption>Rate</RequestOption>
		    </Request>
		<PickupType>
		    <Code>01</Code>
		</PickupType>
		<Shipment>
		    <Shipper>
			<Address>
			    <PostalCode>".addslashes($fromZip)."</PostalCode>
			    <CountryCode>US</CountryCode>
			</Address>
		    <ShipperNumber>$this->shipperNumber</ShipperNumber>
		    </Shipper>
		    <ShipTo>
			<Address>
			    <PostalCode>".addslashes($toZip)."</PostalCode>
			    <CountryCode>US</CountryCode>
			<ResidentialAddressIndicator/>
			</Address>
		    </ShipTo>
		    <ShipFrom>
			<Address>
			    <PostalCode>".addslashes($fromZip)."</PostalCode>
			    <CountryCode>US</CountryCode>
			</Address>
		    </ShipFrom>
		    <Service>
			<Code>".addslashes($service)."</Code>
		    </Service>
		    <Package>
			<PackagingType>
			    <Code>02</Code>
			</PackagingType>
			<Dimensions>
			    <UnitOfMeasurement>
				<Code>IN</Code>
			    </UnitOfMeasurement>
			    <Length>".  addslashes($length)."</Length>
			    <Width>".addslashes($width)."</Width>
			    <Height>".addslashes($height)."</Height>
			</Dimensions>
			<PackageWeight>
			    <UnitOfMeasurement>
				<Code>LBS</Code>
			    </UnitOfMeasurement>
			    <Weight>".addslashes($weight)."</Weight>
			</PackageWeight>
		    </Package>
		</Shipment>
        </RatingServiceSelectionRequest>";

        $result = $this->BUtil->remoteHttp('POST', $this->rateApiUrl, $data);

        //echo '<!-- '. $result. ' -->'; // THIS LINE IS FOR DEBUG PURPOSES ONLY-IT WILL SHOW IN HTML COMMENTS
        $ratings = new SimpleXMLElement($result);
        $this->response = $ratings;
        //echo '<!-- '. print_r($ratings, 1). ' -->'; // THIS LINE IS FOR DEBUG PURPOSES ONLY-IT WILL SHOW IN HTML COMMENTS

        if ($this->isError()) {
            return false;
        }
        return $this->getTotal();
    }

    public function getTotal()
    {
        return (string) $this->response->RatedShipment->TotalCharges->MonetaryValue;
    }

    public function getEstimate()
    {
        return (string) $this->response->RatedShipment->GuaranteedDaysToDelivery;
    }

    public function isError()
    {
        if ($this->response->Response->ResponseStatusCode != 1) {
            return true;
        }
        return false;
    }

    public function getError()
    {
        return (string) $this->response->Response->Error->ErrorDescription;
    }
}
