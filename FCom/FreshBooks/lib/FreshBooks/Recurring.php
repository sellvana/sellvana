<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Recurring Class
 *
 *
 * @package    FreshBooks

 * @copyright  Milan Rukavina, rukavinamilan@gmail.com
 * @version    1.0
 */

include_once 'BaseInvoice.php';
/**
 * Class representing invoice API 
 */
class FreshBooks_Recurring extends FreshBooks_BaseInvoice 
{
	protected $_elementName = "recurring";
	
	public $recurringId = "";
	public $amountOutstanding = "";	
	public $occurrences = "";
	public $frequency = "";
	public $stopped = "";
	public $sendEmail = "";
	public $sendSnailMail = "";
	
/**
 * return XML content
 */	
	protected function _internalXMLContent()
	{
		$content =
							$this->_getTagXML("recurring_id",$this->recurringId) .
							$this->_getTagXML("amount_outstanding",$this->amountOutstanding) .
							$this->_getTagXML("occurrences",$this->occurrences) .
							$this->_getTagXML("frequency",$this->frequency) .
							$this->_getTagXML("stopped",$this->stopped) .
							$this->_getTagXML("send_email",$this->sendEmail) .
							$this->_getTagXML("send_snail_mail",$this->sendSnailMail) .
							
							parent::_internalXMLContent();
							
		return $content;
		
	}
	
/**
 * load obect properties from SimpleXML object
 */	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->recurringId = (string)$XMLObject->recurring_id;		
		$this->amountOutstanding = (string)$XMLObject->amount_outstanding;
		
		$this->occurrences = (string)$XMLObject->occurrences;
		$this->frequency = (string)$XMLObject->frequency;
		$this->stopped = (string)$XMLObject->stopped;
		$this->sendEmail = (string)$XMLObject->send_email;
		$this->sendSnailMail = (string)$XMLObject->send_snail_mail;
		
		parent::_internalLoadXML($XMLObject);
	}

/**
 * prepare XML string request for CREATE server method
 */			
	protected function _internalCreate($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			$this->recurringId = (string)$XMLObject->recurring_id;
		}
	}	

/**
 * prepare XML string request for GET server method
 */		
	protected function _internalPrepareGet($id,&$content)
	{
		$content = $this->_getTagXML("recurring_id",$id);
	}
	
/**
 * process XML string response from GET server method
 */		
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->recurring);
	}
	
/**
 * prepare XML string request for DELETE server method
 */		
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("recurring_id",$this->recurringId);
	}
	
/**
 * process XML string response from DELETE server method
 */		
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		parent::_internalDelete($responseStatus,$XMLObject);
		if($responseStatus){
			unset($this->recurringId);
			unset($this->amountOutstanding);
			
			unset($this->occurrences);
			unset($this->frequency);
			unset($this->stopped);
			unset($this->sendEmail);
			unset($this->sendSnailMail);
		}
	}
	
/**
 * prepare XML string request for LIST server method
 */	
	protected function _internalPrepareListing($filters,&$content)
	{
		if(is_array($filters) && count($filters)){
			$content 	.= parent::_internalPrepareListing($filters,$content);
		}
	}

/**
 * process XML string response from LIST server method
 */	
	protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo)
	{
		$rows = array();
		$resultInfo = array();
		
		$recurrings = $XMLObject->recurrings;
		$resultInfo['page'] = (string)$recurrings['page'];
		$resultInfo['perPage'] = (string)$recurrings['per_page'];
		$resultInfo['pages'] = (string)$recurrings['pages'];
		$resultInfo['total'] = (string)$recurrings['total'];

		foreach ($recurrings->children() as $key=>$currXML){
			$thisRecurring = new FreshBooks_Recurring();
			$thisRecurring->_internalLoadXML($currXML);
			$rows[] = $thisRecurring;
		}
	}
	
/**
 * prepare XML string request for SENDBYEMAIL server method
 */	
	protected function _internalPrepareSendByEmail(&$content)
	{
		//
	}
	
/**
 * process XML string response from SENDBYEMAIL server method
 */	
	protected function _internalSendByEmail($responseStatus,&$XMLObject)
	{
		//
	}

/**
 * overrides send email since not supported; returns false
 */	
	public function sendEmail(){
		return false;
	}
}
