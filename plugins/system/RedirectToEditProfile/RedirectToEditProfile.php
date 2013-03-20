<?php

defined('_JEXEC') or die('Restricted access to this plugin'); 

jimport('joomla.plugin.plugin');

class plgSystemRedirecttoEditProfile extends JPlugin
{
	function __construct(& $subject, $params )
	{
		parent::__construct( $subject, $params );
	}
	
	function onAfterRoute()
	{
		global $mainframe;
		
		if($mainframe->isAdmin())
			return;
		
		if($this->isComponentExists('community') === false)
			return;
			
		$option = JRequest::getCmd('option');
		$view 	= JRequest::getCmd('view');
		$task 	= JRequest::getCmd('task');
	
		if($option == 'com_community' && $view == 'profile' && $task == 'edit')
			return;
	
		if($option == 'com_community' && $view == 'profile' && $task == 'editDetails')
			return;
	
		if($option == 'com_user' && $task == 'logout')
			return;
			
		if($option == 'com_login' || $option == 'com_acctexp')
			return;
		
		$userid 	=& JFactory::getUser()->id;
	
		// For Guest, do nothing and just return, let the joomla handle it
		if(!$userid) 
			return;
		
		// if admin then return 
		if($this->isSuperAdministrator())
			return;
		
		//when user profile is complete (already cached)
		if($this->userParameter($userid,'profilecomplete','get'))
			return;
	
		$plugin =& JPluginHelper::getPlugin('system', 'RedirectToEditProfile');
 		$params = new JParameter($plugin->params);
	
		//whenredirect 0 means redirect only at login else everytime
		$whenredirect = $params->get('whenredirect',0);
		$whichfield = $params->get('whichfield',0);
		$message  = $params->get('message');
	
		if($whenredirect == 0 && !$this->userParameter($userid,'justloggedin','get'))
			return;
	
		$this->userParameter($userid,'justloggedin','unset');
	
		$url = CRoute::_('index.php?option=com_community&view=profile&task=edit',false);
	
		if (!$this->checkCompleteField($userid,$whichfield))
			$mainframe->redirect( $url,$message);
	}


	function onLoginUser($user,$option)
	{
		global $mainframe;
		
		if($mainframe->isAdmin())
			return;

		if($this->isComponentExists('community') === false)
			return;
			
		$option = JRequest::getCmd('option');
		$view 	= JRequest::getCmd('view');
		$task 	= JRequest::getCmd('task');
	
		$userid 	=& JFactory::getUser($user['username'])->id;
		
		// For Guest, do nothing and just return, let the joomla handle it
		if(!$userid) 
			return;
	
		// if admin then return
		if($this->isSuperAdministrator())
			return;
	
		$this->userParameter($userid,'justloggedin','set');
	
		$plugin =& JPluginHelper::getPlugin('system', 'RedirectToEditProfile');
 		$params = new JParameter($plugin->params);
	
		$whichfield = $params->get('whichfield',0);
	
		if ($this->checkCompleteField($userid,$whichfield))
			$this->userParameter($userid,'profilecomplete','set');
		else
			$this->userParameter($userid,'profilecomplete','unset');
	}


	function getProfile($userid)
	{
		$pModel = CFactory::getModel('profile');
		$profile = $pModel->getEditableProfile($userid);
		return $profile['fields'];
	}

	function checkCompleteField($userid,$required)
	{	
		global $mainframe;
		
		$db =& JFactory::getDBO();
		$count = 0;
		$empty = 0;
		$fields = $this->getProfile($userid);
		$pModel = CFactory::getModel('profile');
	
		foreach($fields as $name => $fieldGroup)
		{
			foreach($fieldGroup as $field)
			{
				if($required == 1 && $field['required'])
					$whichfield = 1;
				else if($required == 1 && !$field['required'])
					$whichfield = 0;
				else if($required == 0)
					$whichfield = 1;
			
				if($whichfield)
				{
					$count++;
					if($pModel->_fieldValueExists($field['fieldcode'],$userid))
					{
						$query = 'SELECT value FROM #__community_fields_values' . ' '
						. 'WHERE `field_id`=' .$db->Quote( $field['id'] ) . ' '
						. 'AND `user_id`=' . $db->Quote( $userid );

						$db->setQuery( $query );
						if($db->getErrorNum()) {
							JError::raiseError( 500, $db->stderr());
						}

						$result = $db->loadResult();
						if(empty($result))
							return false; 
					}
					else
						return false;
				}
			}
		}
	
		return true;
	}
	
	/* function set_user_parameter($userid,$paramname)
	{
		$db			=& JFactory::getDBO();
		$user =& JUser::getInstance((int)$userid);
		$user->setParam($paramname, '1');
		$user->save();
	}

	function unset_user_parameter($userid,$paramname)
	{
		$db			=& JFactory::getDBO();
		$user =& JUser::getInstance((int)$userid);
		$user->setParam($paramname, '0');
		$user->save();
	}

	function get_user_parameter($userid,$paramname)
	{
		$user =& JUser::getInstance((int)$userid);
		$profilecomplete	= $user->getParam($paramname,'0');
		return $profilecomplete;
	}*/
	function userParameter($userid,$paramname,$status)
	{
		if($status =='get')
		{
			$user =& JUser::getInstance((int)$userid);
			$profilecomplete	= $user->getParam($paramname,'0');
			return $profilecomplete;
		}
		else
		{
			$db			=& JFactory::getDBO();
			$user =& JUser::getInstance((int)$userid);
			if($status =='set')
			{
				$user->setParam($paramname, '1');
				$user->save();
				return;
			}
			if($status =='unset')
			{
				$user->setParam($paramname, '0');
				$user->save();
				return;
			}
		}
	}
	
	
	function isSuperAdministrator($userid = null)
	{
		$loggedinUser	= JFactory::getUser($userid);
		if(!$loggedinUser)
			return false;
					
		return ( $loggedinUser->usertype == 'Super Administrator' || $loggedinUser->usertype == 'Administrator');
	}
	
	
	function isComponentExists($comName)
	{
		$comSitePath	=	JPATH_ROOT . DS . 'components' .DS.	'com_' . $comName;
		$comAdminPath	=	JPATH_ADMINISTRATOR . DS . 'components' .DS.	'com_' . $comName;
		
		jimport('joomla.filesystem.folder');
		
		if(!JFolder::exists($comSitePath) || !JFolder::exists($comAdminPath))
			return false;
			
		require_once (JPATH_ROOT. DS.'components'.DS.'com_community'.DS.'libraries'.DS.'core.php');
		return true;
	}
}
?>