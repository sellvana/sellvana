<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks TimeEntry Class
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
 * Class representing time_entry API 
 */
class FreshBooks_TimeEntry extends FreshBooks_ElementAction implements FreshBooks_Element_Interface,FreshBooks_ElementAction_Interface
{
	protected $_elementName = "time_entry";
	
	public $timeEntryId = "";
	public $projectId = "";
	public $taskId = "";	
	public $date = "";
	public $notes = "";
	public $hours = "";
	public $staffId = "";
	
/**
 * return XML string
 */	
	public function asXML()
	{
		$content =
							$this->_getTagXML("time_entry_id",$this->timeEntryId) .
							$this->_getTagXML("project_id",$this->projectId) .
							$this->_getTagXML("task_id",$this->taskId) .
							$this->_getTagXML("date",$this->date) .
							$this->_getTagXML("notes",$this->notes) .
							$this->_getTagXML("hours",$this->hours);
							
		return $this->_getTagXML("time_entry",$content);
		
	}
	
/**
 * load obect properties from SimpleXML object
 */	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->timeEntryId = (string)$XMLObject->time_entry_id;
		$this->projectId = (string)$XMLObject->project_id;
		$this->taskId = (string)$XMLObject->task_id;		
		$this->date = (string)$XMLObject->date;
		$this->notes = (string)$XMLObject->notes;
		$this->hours = (string)$XMLObject->hours;
		$this->staffId = (string)$XMLObject->staff_id;
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
			$this->timeEntryId = (string)$XMLObject->time_entry_id;
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
		$content = $this->_getTagXML("time_entry_id",$id);
	}
	
/**
 * process XML string response from GET server method
 */	
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->time_entry);
	}
	
/**
 * prepare XML string request for DELETE server method
 */		
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("time_entry_id",$this->timeEntryId);
	}
	
/**
 * process XML string response from DELETE server method
 */		
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			unset($this->timeEntryId);
			unset($this->projectId);
			unset($this->taskId);		
			unset($this->date);
			unset($this->notes);
			unset($this->hours);
			unset($this->staffId);
		}
	}
	
/**
 * prepare XML string request for LIST server method
 */		
	protected function _internalPrepareListing($filters,&$content)
	{
		if(is_array($filters) && count($filters)){
			$content 	.= $this->_getTagXML("project_id",$filters['projectId'])
								.  $this->_getTagXML("task_id",$filters['taskId'])
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
		$timeEntries = $XMLObject->time_entries;
		$resultInfo['page'] = (string)$timeEntries['page'];
		$resultInfo['perPage'] = (string)$timeEntries['per_page'];
		$resultInfo['pages'] = (string)$timeEntries['pages'];
		$resultInfo['total'] = (string)$timeEntries['total'];

		foreach ($timeEntries->children() as $key=>$currXML){
			$thisTimeEntry = new FreshBooks_TimeEntry();
			$thisTimeEntry->_internalLoadXML($currXML);
			$rows[] = $thisTimeEntry;
		}
	}
}
