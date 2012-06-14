<?php
class UpsRate {
    protected $AccessLicenseNumber;
    protected $UserId;
    protected $Password;
    protected $shipperNumber;
    protected $credentials;

    protected $response;

    function UpsRate($access,$user,$pass,$shipper) {
	$this->AccessLicenseNumber = $access;
	$this->UserID = $user;
	$this->Password = $pass;
	$this->shipperNumber = $shipper;
	$this->credentials = 1;
    }

    // Define the function getRate() - no parameters
    public function getRate($PostalCode,$dest_zip,$service,$length,$width,$height,$weight)
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
			    <PostalCode>$PostalCode</PostalCode>
			    <CountryCode>US</CountryCode>
			</Address>
		    <ShipperNumber>$this->shipperNumber</ShipperNumber>
		    </Shipper>
		    <ShipTo>
			<Address>
			    <PostalCode>$dest_zip</PostalCode>
			    <CountryCode>US</CountryCode>
			<ResidentialAddressIndicator/>
			</Address>
		    </ShipTo>
		    <ShipFrom>
			<Address>
			    <PostalCode>$PostalCode</PostalCode>
			    <CountryCode>US</CountryCode>
			</Address>
		    </ShipFrom>
		    <Service>
			<Code>$service</Code>
		    </Service>
		    <Package>
			<PackagingType>
			    <Code>02</Code>
			</PackagingType>
			<Dimensions>
			    <UnitOfMeasurement>
				<Code>IN</Code>
			    </UnitOfMeasurement>
			    <Length>$length</Length>
			    <Width>$width</Width>
			    <Height>$height</Height>
			</Dimensions>
			<PackageWeight>
			    <UnitOfMeasurement>
				<Code>LBS</Code>
			    </UnitOfMeasurement>
			    <Weight>$weight</Weight>
			</PackageWeight>
		    </Package>
		</Shipment>
		</RatingServiceSelectionRequest>";
		$ch = curl_init("https://www.ups.com/ups.app/xml/Rate");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_TIMEOUT, 60);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
		$result=curl_exec ($ch);
                //echo '<!-- '. $result. ' -->'; // THIS LINE IS FOR DEBUG PURPOSES ONLY-IT WILL SHOW IN HTML COMMENTS
                $ratings = new SimpleXMLElement($result);
                $this->response = $ratings;
                echo '<!-- '. print_r($ratings, 1). ' -->'; // THIS LINE IS FOR DEBUG PURPOSES ONLY-IT WILL SHOW IN HTML COMMENTS
                if ($ratings->Response->ResponseStatusCode == 1) {
                    return (string) $ratings->RatedShipment->TotalCharges->MonetaryValue;
                }
        return false;

    }

    public function getEstimate ()
    {
        return (string) $this->response->RatedShipment->GuaranteedDaysToDelivery;
    }

    public function getError()
    {
        return (string) $this->response->Response->Error->ErrorDescription;
    }
}
