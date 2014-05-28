<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Payment Class
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
 * Class representing payment API 
 */
class FreshBooks_Payment extends FreshBooks_ElementAction implements FreshBooks_Element_Interface,FreshBooks_ElementAction_Interface
{
	protected $_elementName = "payment";
	
	public $paymentId = "";
	public $clientId = "";
	public $invoiceId = "";
	public $date = "";
	public $amount = "";
	public $type = "";
	public $notes = "";
	public $status = "";
		
/**
 * return XML string
 */		
	public function asXML()
	{
		$content =
							$this->_getTagXML("payment_id",$this->paymentId) .
							$this->_getTagXML("client_id",$this->clientId) .
							$this->_getTagXML("invoice_id",$this->invoiceId) .
							$this->_getTagXML("amount",$this->amount) .
							$this->_getTagXML("date",$this->date) .
							$this->_getTagXML("notes",$this->notes) .
							$this->_getTagXML("type",$this->type);
							
		return $this->_getTagXML("payment",$content);
		
	}
	
/**
 * load obect properties from SimpleXML object
 */ 	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->paymentId = (string)$XMLObject->payment_id;
		$this->clientId = (string)$XMLObject->client_id;
		$this->invoiceId = (string)$XMLObject->invoice_id;		
		$this->amount = (string)$XMLObject->amount;
		$this->date = (string)$XMLObject->date;
		$this->notes = (string)$XMLObject->notes;
		$this->type = (string)$XMLObject->type;
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
			$this->paymentId = (string)$XMLObject->payment_id;
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
		$content = $this->_getTagXML("payment_id",$id);
	}
	
/**
 * process XML string response from GET server method
 */	
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->payment);
	}
	
/**
 * prepare XML string request for DELETE server method
 */		
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("payment_id",$this->paymentId);
	}
	
/**
 * process XML string response from DELETE server method
 */	
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			unset($this->paymentId);
			unset($this->invoiceId);
			unset($this->clientId);		
			unset($this->amount);
			unset($this->date);
			unset($this->notes);
			unset($this->type);
		}
	}
	
/**
 * prepare XML string request for LIST server method
 */	
	protected function _internalPrepareListing($filters,&$content)
	{
		if(is_array($filters) && count($filters)){
			$content 	.= $this->_getTagXML("client_id",$filters['clientId'])
								.  $this->_getTagXML("invoice_id",$filters['invoiceId'])
								.  $this->_getTagXML("status",$filters['status'])
								.  $this->_getTagXML("date_from",$filters['dateFrom'])
								.  $this->_getTagXML("date_to",$filters['dateTo']);
		}
	}
	
/**
 * process XML string response from LIST server method
 */	
	protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo)
	{
		$rows = array();
		$resultInfo = array();
		$payments = $XMLObject->payments;
		$resultInfo['page'] = (string)$payments['page'];
		$resultInfo['perPage'] = (string)$payments['per_page'];
		$resultInfo['pages'] = (string)$payments['pages'];
		$resultInfo['total'] = (string)$payments['total'];

		foreach ($payments->children() as $key=>$currXML){
			$thisPayment = new FreshBooks_Payment();
			$thisPayment->_internalLoadXML($currXML);
			$rows[] = $thisPayment;
		}
	}
}
