<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Invoice Class
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
class FreshBooks_Invoice extends FreshBooks_BaseInvoice
{
	protected $_elementName = "invoice";

	public $invoiceId = "";
	public $amountOutstanding = "";
	public $recurringId = "";

    public $subject;
    public $message;

    public $paypalType = 'b2b';

/**
 * return XML content
 */
	protected function _internalXMLContent()
	{
		$content =
			$this->_getTagXML("invoice_id",$this->invoiceId) .
			$this->_getTagXML("amount_outstanding",$this->amountOutstanding) .
			$this->_getTagXML("recurringId",$this->recurringId) .

            $this->_getTagXML("gateways",'<gateway><name>Paypal</name><type>'.$this->paypalType.'</type></gateway><gateway><name>Google Checkout</name></gateway>') .

			parent::_internalXMLContent();

		return $content;

	}

/**
 * load obect properties from SimpleXML object
 */
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->invoiceId = (string)$XMLObject->invoice_id;
		$this->amountOutstanding = (string)$XMLObject->amount_outstanding;
		$this->recurringId = (string)$XMLObject->recurring_id;
		parent::_internalLoadXML($XMLObject);
	}

/**
 * prepare XML string request for CREATE server method
 */
	protected function _internalCreate($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			$this->invoiceId = (string)$XMLObject->invoice_id;
		}
	}

/**
 * prepare XML string request for GET server method
 */
	protected function _internalPrepareGet($id,&$content)
	{
		$content = $this->_getTagXML("invoice_id",$id);
	}

/**
 * process XML string response from GET server method
 */
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->invoice);
	}

/**
 * prepare XML string request for DELETE server method
 */
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("invoice_id",$this->invoiceId);
	}

/**
 * process XML string response from DELETE server method
 */
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		parent::_internalDelete($responseStatus,$XMLObject);
		if($responseStatus){
			unset($this->invoiceId);
			unset($this->amountOutstanding);
			unset($this->recurringId);
		}
	}

/**
 * prepare XML string request for LIST server method
 */
	protected function _internalPrepareListing($filters,&$content)
	{
		if(is_array($filters) && count($filters)){
			$content 	.= parent::_internalPrepareListing($filters,$content)
								.  $this->_getTagXML("recurring_id",$filters['recurringId']);
		}
	}

/**
 * process XML string response from LIST server method
 */
	protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo)
	{
		$rows = array();
		$resultInfo = array();

		$invoices = $XMLObject->invoices;
		$resultInfo['page'] = (string)$invoices['page'];
		$resultInfo['perPage'] = (string)$invoices['per_page'];
		$resultInfo['pages'] = (string)$invoices['pages'];
		$resultInfo['total'] = (string)$invoices['total'];

		foreach ($invoices->children() as $key=>$currXML){
			$thisInvoice = new FreshBooks_Invoice();
			$thisInvoice->_internalLoadXML($currXML);
			$rows[] = $thisInvoice;
		}
	}

/**
 * prepare XML string request for SENDBYEMAIL server method
 */
	protected function _internalPrepareSendByEmail(&$content)
	{
		$content = $this->_getTagXML("invoice_id",$this->invoiceId)
            .$this->_getTagXML("subject", $this->subject)
            .$this->_getTagXML("message", $this->message);
	}

/**
 * process XML string response from SENDBYEMAIL server method
 */
	protected function _internalSendByEmail($responseStatus,&$XMLObject)
	{
		//
	}

/**
 * send invoice by snail mail
 */
	public function sendBySnailMail(){
		$content = $this->_getTagXML("invoice_id",$this->invoiceId);
		$responseXML = $this->_sendRequest($content,"sendBySnailMail");
		$responseStatus = $this->_processResponse($responseXML);
		return $responseStatus;
	}
}
