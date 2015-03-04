<?php
/**
* @Copyright Ready Bytes Software Labs Pvt. Ltd. (C) 2010- author-Team Joomlaxi
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.form.formfield');
include_once JPATH_ROOT.'/components/com_xipt/api.xipt.php';

class JFormFieldProfiletype extends JFormField
{
	public $type = 'Profiletype';
		
	function getInput(){

		// get array of all visible profile types (std-class)
		$pTypeArray = XiptAPI::getProfileTypeIds();
		
		if(isset($this->element['addall'])){
			$reqall 		= new stdClass();
			$reqall->id 	= 0;
			$reqall->name 	= 'All';
			array_unshift($pTypeArray, $reqall);
		}
		
		if(isset($this->element['addnone'])){
			$reqnone 		= new stdClass();
			$reqnone->id 	= -1;
			$reqnone->name 	= 'None';
			$pTypeArray[]	= $reqnone;
		}
		//add multiselect option
		$attr = ' ';
		
		if($this->multiple){
			$attr .= ' multiple="multiple"';
		}
		
		if($size = $this->element['size']){
			$attr .= ' size="'.$size.'"';
		}
		
		return JHTML::_('select.genericlist',  $pTypeArray, $this->name, $attr, 'id', 'name', $this->value);
	}
}