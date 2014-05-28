<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Task Class
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
 * Class representing task API 
 */
class FreshBooks_Task extends FreshBooks_ElementAction implements FreshBooks_Element_Interface,FreshBooks_ElementAction_Interface
{
	protected $_elementName = "task";
	
	public $taskId = "";
	public $name = "";
	public $billable = "";
	public $rate = "";
	public $description = "";
	
/**
 * return XML string
 */	
	public function asXML()
	{
		$content =
							$this->_getTagXML("task_id",$this->taskId) .
							$this->_getTagXML("name",$this->name) .
							$this->_getTagXML("billable",$this->billable) .
							$this->_getTagXML("rate",$this->rate) .
							$this->_getTagXML("description",$this->description);
							
		return $this->_getTagXML("task",$content);
		
	}
	
/**
 * load obect properties from SimpleXML object
 */	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->taskId = (string)$XMLObject->task_id;
		$this->name = (string)$XMLObject->name;
		$this->billable = (string)$XMLObject->billable;
		$this->rate = (string)$XMLObject->rate;		
		$this->description = (string)$XMLObject->description;
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
			$this->taskId = (string)$XMLObject->task_id;
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
		$content = $this->_getTagXML("task_id",$id);
	}
	
/**
 * process XML string response from GET server method
 */	
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->task);
	}
	
/**
 * prepare XML string request for DELETE server method
 */	
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("task_id",$this->taskId);
	}
	
/**
 * process XML string response from DELETE server method
 */		
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			unset($this->taskId);
			unset($this->name);
			unset($this->billable);
			unset($this->rate);
			unset($this->description);		
		}
	}
	
/**
 * prepare XML string request for LIST server method
 */		
	protected function _internalPrepareListing($filters,&$content)
	{
		if(is_array($filters) && count($filters)){
			$content .= $this->_getTagXML("project_id",$filters['projectId']);
		}
	}
	
/**
 * process XML string response from LIST server method
 */	
	protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo)
	{
		$rows = array();
		$resultInfo = array();
		$tasks = $XMLObject->tasks;
		$resultInfo['page'] = (string)$tasks['page'];
		$resultInfo['perPage'] = (string)$tasks['per_page'];
		$resultInfo['pages'] = (string)$tasks['pages'];
		$resultInfo['total'] = (string)$tasks['total'];

		foreach ($tasks->children() as $key=>$currXML){
			$thisTasks = new FreshBooks_Task();
			$thisTasks->_internalLoadXML($currXML);
			$rows[] = $thisTasks;
		}
	}
}
