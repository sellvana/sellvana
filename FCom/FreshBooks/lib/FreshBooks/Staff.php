<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Staff Class
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
 * Class representing staff API 
 */
class FreshBooks_Staff extends FreshBooks_ElementAction implements FreshBooks_Element_Interface,FreshBooks_ElementAction_Interface
{
	protected $_elementName = "staff";
	
	public $staffId = "";
	public $username = "";
	public $firstName = "";
	public $lastName = "";
	public $email = "";	
	public $businessPhone = "";
	public $mobilePhone = "";
	public $rate = "";
	public $lastLogin = "";
	public $numberOfLogins = "";
	public $signupDate = "";
	public $street1 = "";
	public $street2 = "";
	public $city = "";
	public $state = "";
	public $country = "";
	public $code = "";
	
/**
 * return XML string
 */ 	
	public function asXML()
	{
		$content =
							$this->_getTagXML("staff_id",$this->staffId) .
							$this->_getTagXML("username",$this->username) .
							$this->_getTagXML("first_name",$this->firstName) .
							$this->_getTagXML("last_name",$this->lastName) .
							$this->_getTagXML("email",$this->email) .
							
							$this->_getTagXML("business_hone",$this->businessPhone) .
							$this->_getTagXML("mobile_phone",$this->mobilePhone) .
							$this->_getTagXML("rate",$this->rate) .
							$this->_getTagXML("last_login",$this->lastLogin) .
							$this->_getTagXML("number_of_logins",$this->numberOfLogins) .
							$this->_getTagXML("signup_date",$this->signupDate) .
							
							$this->_getTagXML("street1",$this->street1) .
							$this->_getTagXML("street2",$this->street2) .
							$this->_getTagXML("city",$this->city) .
							$this->_getTagXML("state",$this->state) .
							$this->_getTagXML("country",$this->country) .
							$this->_getTagXML("code",$this->code);
		return $this->_getTagXML("staff",$content);
		
	}

/**
 * load obect properties from SimpleXML object
 */	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->staffId = (string)$XMLObject->staff_id;
		$this->username = (string)$XMLObject->username;
		$this->firstName = (string)$XMLObject->first_name;
		$this->lastName = (string)$XMLObject->last_name;
		$this->email = (string)$XMLObject->email;		
		
		$this->businessPhone = (string)$XMLObject->business_phone;
		$this->mobilePhone = (string)$XMLObject->mobile_phone;
		$this->rate = (string)$XMLObject->rate;
		$this->lastLogin = (string)$XMLObject->last_login;
		$this->numberOfLogins = (string)$XMLObject->number_of_logins;
		$this->signupDate = (string)$XMLObject->signup_date;
		
		$this->street1 = (string)$XMLObject->street1;
		$this->street2 = (string)$XMLObject->street2;
		$this->city = (string)$XMLObject->city;
		$this->state = (string)$XMLObject->state;
		$this->country = (string)$XMLObject->country;
		$this->code = (string)$XMLObject->code;	
	}
	
	protected function _internalPrepareCreate(&$content)
	{
		//
	}
	
	protected function _internalCreate($responseStatus,&$XMLObject)
	{
		//
	}
	
/**
 * create not supported - returns false
 */ 	
	public function create(){
		return false;
	}
	
	protected function _internalPrepareUpdate(&$content)
	{
		//
	}
	
	protected function _internalUpdate($responseStatus,&$XMLObject)
	{
		//
	}

/**
 * update not supported - returns false
 */ 	
	public function update(){
		return false;
	}
	
/**
 * prepare XML string request for GET server method
 */ 	
	protected function _internalPrepareGet($id,&$content)
	{
		$content = $this->_getTagXML("staff_id",$id);
	}
	
/**
 * process XML string response from GET server method
 */		
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->staff);
	}
	
/**
 * delete not supported - returns false
 */	
	public function delete(){
		return false;
	}
	
	protected function _internalPrepareDelete(&$content)
	{
		//
	}
	
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		//
	}

/**
 * prepare XML string request for LIST server method
 */	
	protected function _internalPrepareListing($filters,&$content)
	{
		//
	}
	
/**
 * process XML string response from LIST server method
 */	
	protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo)
	{
		$rows = array();
		$resultInfo = array();
		$staffMembers = $XMLObject->staff_members;
		$resultInfo['page'] = (string)$staffMembers['page'];
		$resultInfo['perPage'] = (string)$staffMembers['per_page'];
		$resultInfo['pages'] = (string)$staffMembers['pages'];
		$resultInfo['total'] = (string)$staffMembers['total'];

		foreach ($staffMembers->children() as $key=>$currXML){
			$thisMember = new FreshBooks_Staff();
			$thisMember->_internalLoadXML($currXML);
			$rows[] = $thisMember;
		}
	}
}
