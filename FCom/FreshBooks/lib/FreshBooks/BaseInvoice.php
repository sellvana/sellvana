<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Invoice Class
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
 * Class representing base invoice API - abstract class to be inherited in invoice, estimate, recurring
 */
abstract class FreshBooks_BaseInvoice extends FreshBooks_ElementAction implements FreshBooks_Element_Interface,FreshBooks_ElementAction_Interface
{
	public $clientId = "";
	public $number = "";
	public $amount = "";
	public $status = "";
	public $date = "";
	public $poNumber = "";
	public $discount = "";
	public $notes = "";
	public $terms = "";

	public $linkClientView = "";
	public $linkView = "";
	public $linkEdit = "";

	public $organization = "";
	public $firstName = "";
	public $lastName = "";

	public $pStreet1 = "";
	public $pStreet2 = "";
	public $pCity = "";
	public $pState = "";
	public $pCountry = "";
	public $pCode = "";

/**
 * invoice lines (items)
 */
	public $lines = array();

/**
 * generate XML string from common properties
 */
	protected function _internalXMLContent(){
		$content =
							$this->_getTagXML("client_id",$this->clientId) .
							$this->_getTagXML("number",$this->number) .
							$this->_getTagXML("amount",$this->amount) .
							$this->_getTagXML("status",$this->status) .
							$this->_getTagXML("date",$this->date) .
							$this->_getTagXML("po_number",$this->poNumber) .
							$this->_getTagXML("discount",$this->discount) .
							$this->_getTagXML("notes",$this->notes) .
							$this->_getTagXML("terms",$this->terms) .
							$this->_linksAsXML() .
							$this->_getTagXML("organization",$this->organization) .
							$this->_getTagXML("first_name",$this->firstName) .
							$this->_getTagXML("last_name",$this->lastName) .
							$this->_getTagXML("p_street1",$this->pStreet1) .
							$this->_getTagXML("p_street2",$this->pStreet2) .
							$this->_getTagXML("p_city",$this->pCity) .
							$this->_getTagXML("p_state",$this->pState) .
							$this->_getTagXML("p_country",$this->pCountry) .
							$this->_getTagXML("p_code",$this->pCode) .

							$this->_linesAsXML();
		return $content;
	}

/**
 * return XML string
 */
	public function asXML()
	{
		return $this->_getTagXML($this->_elementName,$this->_internalXMLContent());
	}

/**
 * generate XML output from links properties
 */
	protected function _linksAsXML(){
		$content  = $this->_getTagXML("client_view",$this->linkClientView)
							. $this->_getTagXML("view",$this->linkView)
							. $this->_getTagXML("edit",$this->linkEdit);

		return $this->_getTagXML("links",$content);
	}

/**
 * generate XML output from lines array
 */
	protected function _linesAsXML(){

		$content = "";
		if(count($this->lines)){
			reset($this->lines);
			while(list(,$line) = each($this->lines)){
				$linesXML = $this->_getTagXML("name",$line['name'])
									. $this->_getTagXML("description",$line['description'])
									. $this->_getTagXML("unit_cost",$line['unitCost'])
									. $this->_getTagXML("quantity",$line['quantity'])
									. $this->_getTagXML("amount",$line['amount'])
									. $this->_getTagXML("tax1_name",$line['tax1Name'])
									. $this->_getTagXML("tax2_name",$line['tax2Name'])
									. $this->_getTagXML("tax1_percent",$line['tax1Percent'])
									. $this->_getTagXML("tax2_percent",$line['tax2Percent']);
				$content .= $this->_getTagXML("line",$linesXML);
			}
		}
		return $this->_getTagXML("lines",$content);
	}

/**
 * load obect properties from SimpleXML object
 */
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->clientId = (string)$XMLObject->client_id;

		$this->number = (string)$XMLObject->number;
		$this->amount = (string)$XMLObject->amount;
		$this->status = (string)$XMLObject->status;
		$this->date = (string)$XMLObject->date;
		$this->poNumber = (string)$XMLObject->po_number;
		$this->discount = (string)$XMLObject->discount;
		$this->notes = (string)$XMLObject->notes;
		$this->terms = (string)$XMLObject->terms;
		$this->linkClientView = (string)$XMLObject->links->client_view;
		$this->linkView = (string)$XMLObject->links->view;
		$this->linkEdit = (string)$XMLObject->links->edit;
		$this->organization = (string)$XMLObject->organization;
		$this->firstName = (string)$XMLObject->first_name;
		$this->lastName = (string)$XMLObject->last_name;
		$this->pStreet1 = (string)$XMLObject->p_street1;
		$this->pStreet2 = (string)$XMLObject->p_street2;
		$this->pCity = (string)$XMLObject->p_city;
		$this->pState = (string)$XMLObject->p_state;
		$this->pCountry = (string)$XMLObject->p_country;
		$this->pCode = (string)$XMLObject->p_code;

		$this->_loadLines($XMLObject);
	}

/**
 * load lines array from XML object
 */
	protected function _loadLines(&$XMLObject){
		$lines = $XMLObject->lines;

		foreach ($lines->children() as $key=>$currXML){
			$this->lines[] = array(
															"name"						=> (string)$currXML->name,
															"description"			=> (string)$currXML->description,
															"unitCost"				=> (string)$currXML->unit_cost,
															"quantity"				=> (string)$currXML->quantity,
															"amount"					=> (string)$currXML->amount,
															"tax1Name"				=> (string)$currXML->tax1_name,
															"tax2Name"				=> (string)$currXML->tax2_name,
															"tax1Percent"			=> (string)$currXML->tax1_percent,
															"tax2Percent"			=> (string)$currXML->tax2_percent
			);
		}
	}

/**
 * prepare XML string request for CREATE server method
 */
	protected function _internalPrepareCreate(&$content)
	{
		$content = $this->asXML();
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
 * prepare XML string request for DELETE server method
 */
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			unset($this->clientId);
			unset($this->number);
			unset($this->amount);
			unset($this->status);
			unset($this->date);
			unset($this->poNumber);
			unset($this->discount);
			unset($this->notes);
			unset($this->terms);
			unset($this->linkClientView);
			unset($this->linkView);
			unset($this->linkEdit);
			unset($this->organization);
			unset($this->firstName);
			unset($this->lastName);
			unset($this->pStreet1);
			unset($this->pStreet2);
			unset($this->pCity);
			unset($this->pState);
			unset($this->pCountry);
			unset($this->pCode);
			unset($this->lines);
		}
	}

/**
 * prepare XML string request for LIST server method
 */
	protected function _internalPrepareListing($filters,&$content)
	{
		if(is_array($filters) && count($filters)){
			$content 	.= $this->_getTagXML("client_id",$filters['clientId'])
								.  $this->_getTagXML("status",$filters['status'])
								.  $this->_getTagXML("date_from",$filters['dateFrom'])
								.  $this->_getTagXML("date_to",$filters['dateTo']);
		}
	}

/**
 * internal hook to be implemented in child classes with particular logic to generate request for sendByEmail method
 */
	abstract protected function _internalPrepareSendByEmail(&$content);
/**
 * internal hook to be implemented in child classes with particular logic to process response XML from sendByEmail method
 */
	abstract protected function _internalSendByEmail($responseStatus,&$XMLObject);

/**
 * send invoice by email
 */
	public function sendByEmail(){
		$this->_internalPrepareSendByEmail($content);
		$responseXML = $this->_sendRequest($content,"sendByEmail");
		$responseStatus = $this->_processResponse($responseXML);
		$this->_internalSendByEmail($responseStatus,$responseXML);
		return $responseStatus;
	}
}
