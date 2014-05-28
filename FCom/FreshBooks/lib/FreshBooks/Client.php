<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Client Class
 *
 *
 * @package    FreshBooks

 * @copyright  Milan Rukavina, rukavinamilan@gmail.com
 * @version    1.0
 */

include_once 'ElementAction.php';
include_once 'Element/Interface.php';
include_once 'ElementAction/Interface.php';

/**
 * Class representing client API 
 */
class FreshBooks_Client extends FreshBooks_ElementAction implements FreshBooks_Element_Interface,FreshBooks_ElementAction_Interface
{
	protected $_elementName = "client";
	
	public $clientId = "";
	public $firstName = "";
	public $lastName = "";
	public $organization = "";
	public $email = "";
	public $username = "";
	public $password = "";
	public $workPhone = "";
	public $homePhone = "";
	public $mobile = "";
	public $fax = "";
	public $notes = "";
	
	public $pStreet1 = "";
	public $pStreet2 = "";
	public $pCity = "";
	public $pState = "";
	public $pCountry = "";
	public $pCode = "";
	
	public $sStreet1 = "";
	public $sStreet2 = "";
	public $sCity = "";
	public $sState = "";
	public $sCountry = "";
	public $sCode = "";
	
/**
 * return XML string
 */ 	
	public function asXML()
	{
		$content =
							$this->_getTagXML("client_id",$this->clientId) .
							$this->_getTagXML("first_name",$this->firstName) .
							$this->_getTagXML("last_name",$this->lastName) .
							$this->_getTagXML("organization",$this->organization) .
							$this->_getTagXML("email",$this->email) .
							$this->_getTagXML("username",$this->username) .
							$this->_getTagXML("password",$this->password) .
							$this->_getTagXML("work_phone",$this->workPhone) .
							$this->_getTagXML("home_phone",$this->homePhone) .
							$this->_getTagXML("mobile",$this->mobile) .
							$this->_getTagXML("fax",$this->fax) .
							$this->_getTagXML("notes",$this->notes) .
							$this->_getTagXML("p_street1",$this->pStreet1) .
							$this->_getTagXML("p_street2",$this->pStreet2) .
							$this->_getTagXML("p_city",$this->pCity) .
							$this->_getTagXML("p_state",$this->pState) .
							$this->_getTagXML("p_country",$this->pCountry) .
							$this->_getTagXML("p_code",$this->pCode) .
							$this->_getTagXML("s_street1",$this->sStreet1) .
							$this->_getTagXML("s_street2",$this->sStreet2) .
							$this->_getTagXML("s_city",$this->sCity) .
							$this->_getTagXML("s_state",$this->sState) .
							$this->_getTagXML("s_country",$this->sCountry) .
							$this->_getTagXML("s_code",$this->sCode);
		return $this->_getTagXML("client",$content);
		
	}

/**
 * load obect properties from SimpleXML object
 */ 	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->clientId = (string)$XMLObject->client_id;
		$this->firstName = (string)$XMLObject->first_name;
		$this->lastName = (string)$XMLObject->last_name;
		$this->organization = (string)$XMLObject->organization;
		$this->email = (string)$XMLObject->email;		
		$this->username = (string)$XMLObject->username;
		$this->password = (string)$XMLObject->password;
		$this->workPhone = (string)$XMLObject->work_phone;
		$this->homePhone = (string)$XMLObject->home_phone;
		$this->mobile = (string)$XMLObject->mobile;
		$this->fax = (string)$XMLObject->fax;
		$this->notes = (string)$XMLObject->notes;
		
		$this->pStreet1 = (string)$XMLObject->p_street1;
		$this->pStreet2 = (string)$XMLObject->p_street2;
		$this->pCity = (string)$XMLObject->p_city;
		$this->pState = (string)$XMLObject->p_state;
		$this->pCountry = (string)$XMLObject->p_country;
		$this->pCode = (string)$XMLObject->p_code;
		
		$this->sStreet1 = (string)$XMLObject->s_street1;
		$this->sStreet2 = (string)$XMLObject->s_street2;
		$this->sCity = (string)$XMLObject->s_city;
		$this->sState = (string)$XMLObject->s_state;
		$this->sCountry = (string)$XMLObject->s_country;
		$this->sCode = (string)$XMLObject->s_code;	
	}
	
/**
 * prepare XML string request for CREATE server method
 */ 	
	protected function _internalPrepareCreate(&$content)
	{
		$content = $this->asXML();
	}
	
/**
 * process XML string response from CREATE server method
 */	
	protected function _internalCreate($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			$this->clientId = (string)$XMLObject->client_id;
		}
	}
	
/**
 * prepare XML string request for UPDATE server method
 */ 	
	protected function _internalPrepareUpdate(&$content)
	{
		$content = $this->asXML();
	}
	
/**
 * process XML string response from UPDATE server method
 */		
	protected function _internalUpdate($responseStatus,&$XMLObject)
	{
		//
	}

/**
 * prepare XML string request for GET server method
 */ 	
	protected function _internalPrepareGet($id,&$content)
	{
		$content = $this->_getTagXML("client_id",$id);
	}
	
/**
 * process XML string response from GET server method
 */	
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->client);
	}
	
/**
 * prepare XML string request for DELETE server method
 */	
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("client_id",$this->clientId);
	}
	
/**
 * process XML string response from DELETE server method
 */	
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			unset($this->clientId);
			unset($this->firstName);
			unset($this->lastName);
			unset($this->organization);
			unset($this->email);		
			unset($this->username);
			unset($this->password);
			unset($this->workPhone);
			unset($this->homePhone);
			unset($this->mobile);
			unset($this->fax);
			unset($this->notes);
			
			unset($this->pStreet1);
			unset($this->pStreet2);
			unset($this->pCity);
			unset($this->pState);
			unset($this->pCountry);
			unset($this->pCode);
			
			unset($this->sStreet1);
			unset($this->sStreet2);
			unset($this->sCity);
			unset($this->sState);
			unset($this->sCountry);
			unset($this->sCode);
		}
	}
	
/**
 * prepare XML string request for LIST server method
 */		
	protected function _internalPrepareListing($filters,&$content)
	{
		if(is_array($filters) && count($filters)){
			$content .= $this->_getTagXML("email",$filters['email']) . $this->_getTagXML("username",$filters['username']);
		}
	}
	
/**
 * process XML string response from LIST server method
 */		
	protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo)
	{
		$rows = array();
		$resultInfo = array();
		$clients = $XMLObject->clients;
		$resultInfo['page'] = (string)$clients['page'];
		$resultInfo['perPage'] = (string)$clients['per_page'];
		$resultInfo['pages'] = (string)$clients['pages'];
		$resultInfo['total'] = (string)$clients['total'];

		foreach ($clients->children() as $key=>$currXML){
			$thisClient = new FreshBooks_Client();
			$thisClient->_internalLoadXML($currXML);
			$rows[] = $thisClient;
		}
	}
}
