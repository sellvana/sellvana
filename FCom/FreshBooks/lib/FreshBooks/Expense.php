<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Expense Class
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
 * Class representing expense API 
 */
class FreshBooks_Expense extends FreshBooks_ElementAction implements FreshBooks_Element_Interface,FreshBooks_ElementAction_Interface
{
	protected $_elementName = "expense";
	
	public $expenseId = "";
	public $staffId = "";
	public $categoryId = "";
	public $projectId = "";
	public $clientId = "";
	
	public $amount = "";
	public $date = "";
	public $notes = "";
	public $status = "";
	
	public $tax1Name = "";
	public $tax1Percent = "";
	public $tax1Amount = "";
	public $tax2Name = "";
	public $tax2Percent = "";
	public $tax2Amount = "";	
	
/**
 * return XML string
 */	
	public function asXML()
	{
		$content =
							$this->_getTagXML("expense_id",$this->expenseId) .
							$this->_getTagXML("staff_id",$this->staffId) .
							$this->_getTagXML("category_id",$this->categoryId) .
							$this->_getTagXML("project_id",$this->projectId) .
							$this->_getTagXML("client_id",$this->clientId) .
							$this->_getTagXML("amount",$this->amount) .
							$this->_getTagXML("date",$this->date) .
							$this->_getTagXML("notes",$this->notes) .
							$this->_getTagXML("status",$this->status) .
							$this->_getTagXML("tax1_name",$this->tax1Name) .
							$this->_getTagXML("tax1_percent",$this->tax1Percent) .
							$this->_getTagXML("tax1_amount",$this->tax1Amount) .
							$this->_getTagXML("tax2_name",$this->tax2Name) .
							$this->_getTagXML("tax2_percent",$this->tax2Percent) .
							$this->_getTagXML("tax2_amount",$this->tax2Amount);
							
		return $this->_getTagXML("expense",$content);
		
	}
	
/**
 * load obect properties from SimpleXML object
 */ 	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->expenseId = (string)$XMLObject->expense_id;
		$this->staffId = (string)$XMLObject->staff_id;
		$this->categoryId = (string)$XMLObject->category_id;
		$this->projectId = (string)$XMLObject->project_id;
		$this->clientId = (string)$XMLObject->client_id;		
		$this->amount = (string)$XMLObject->amount;
		$this->date = (string)$XMLObject->date;
		$this->notes = (string)$XMLObject->notes;
		$this->status = (string)$XMLObject->status;
		$this->tax1Name = (string)$XMLObject->tax1_name;
		$this->tax1Percent = (string)$XMLObject->tax1_percent;
		$this->tax1Amount = (string)$XMLObject->tax1_amount;
		$this->tax2Name = (string)$XMLObject->tax2_name;
		$this->tax2Percent = (string)$XMLObject->tax2_percent;
		$this->tax2Amount = (string)$XMLObject->tax2_amount;	
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
			$this->expenseId = (string)$XMLObject->expense_id;
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
		$content = $this->_getTagXML("expense_id",$id);
	}

/**
 * process XML string response from GET server method
 */		
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->expense);
	}
	
/**
 * prepare XML string request for DELETE server method
 */		
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("expense_id",$this->expenseId);
	}
	
/**
 * process XML string response from DELETE server method
 */		
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			unset($this->expenseId);
			unset($this->staffId);
			unset($this->categoryId);
			unset($this->projectId);
			unset($this->clientId);		
			unset($this->amount);
			unset($this->date);
			unset($this->notes);
			unset($this->status);
			unset($this->tax1Name);
			unset($this->tax1Percent);
			unset($this->tax1Amount);
			unset($this->tax2Name);
			unset($this->tax2Percent);
			unset($this->tax2Amount);
		}
	}
	
/**
 * prepare XML string request for LIST server method
 */	
	protected function _internalPrepareListing($filters,&$content)
	{
		if(is_array($filters) && count($filters)){
			$content .= $this->_getTagXML("client_id",$filters['clientId']);
		}
	}
	
/**
 * process XML string response from LIST server method
 */		
	protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo)
	{
		$rows = array();
		$resultInfo = array();
		$expenses = $XMLObject->expenses;
		$resultInfo['page'] = (string)$expenses['page'];
		$resultInfo['perPage'] = (string)$expenses['per_page'];
		$resultInfo['pages'] = (string)$expenses['pages'];
		$resultInfo['total'] = (string)$expenses['total'];

		foreach ($expenses->children() as $key=>$currXML){
			$thisExpense = new FreshBooks_Expense();
			$thisExpense->_internalLoadXML($currXML);
			$rows[] = $thisExpense;
		}
	}
}
