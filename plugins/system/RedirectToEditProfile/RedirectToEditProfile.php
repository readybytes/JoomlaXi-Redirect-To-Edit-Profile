 <?php

/*
* Author 	: Team JoomlaXi @ Ready Bytes Software Labs Pvt. Ltd.
* Email 	: manish@readybytes.in
* License 	: GNU-GPL V2
* (C) www.joomlaxi.com
*/

defined('_JEXEC') or die('Restricted access to this plugin'); 

jimport('joomla.plugin.plugin');

class plgSystemRedirectToEditProfile extends JPlugin
{
	function __construct(& $subject, $params )
	{
		parent::__construct( $subject, $params );
	}
	
	function onAfterRoute()
	{
		$params = $this->params;
	
		//whenredirect 0 means redirect only at login else everytime
		$whenredirect	= $params->get('whenredirect',0);
		$whichfield 	= $params->get('whichfield',0);
		$message  		= $params->get('message');
	
		if(!$whenredirect) { // After login action will be handled by onuserlogin event
			return true; 
		}
	
		
		// Check initial conditions
		if(!self::_isApplicable()) {
			return false;
		}
				
		if (self::_isRedirectRequired(JFactory::getUser()->id, $whichfield)) {
			$url = CRoute::_('index.php?option=com_community&view=profile&task=edit',false);
			JFactory::getApplication()->redirect( $url,$message);
		}
	}


	function onUserLogin($user,$option)
	{
		$userId		= JUserHelper::getUserId($user['username'] );
		
		// Check initial conditions
		if(!self::_isApplicable($userId)) {
			return false;
		}

		$params = $this->params;

		$whichfield = $params->get('whichfield',0);
		$message  	= $params->get('message');

		if (self::_isRedirectRequired($userId, $whichfield)) {
			$url = CRoute::_('index.php?option=com_community&view=profile&task=edit',false);
			//JFactory::getApplication()->redirect( $url,$message);
			// Handle itself by joomla....by this way we dont conflict into existing Joomla login flow or into other extensions
			$app = JFactory::getApplication();
			$app->enqueueMessage($message);
			$app->setUserState('users.login.form.return', $url);
		}
		return true;
	}


	/**
	 * Check initial condition, Plugin task applicable or not
	 */
	private static function _isApplicable($userId = null)
	{
		$app = JFactory::getApplication();
		
		if ($app->isAdmin()) {
			return false;
		}

		// Not applicable when Community does not exist 
		if(!self::_isComponentExists('community')) {
			//XiTODO:: System msg for disable this plugin
			return false;
		}
		
		$input = $app->input;
		
		$option = $input->get('option');
		$view 	= $input->get('view');
		$task 	= $input->get('task');
	
		$user	= JFactory::getUser($userId);
		$userid = $user->id;
		
		// Not Applicable conditions
		if (	 
				( $option == 'com_community' && $view == 'profile' && ( $task == 'edit' || $task == 'editDetails' )) ||
				( $option == 'com_users' && $task == 'user.logout') ||
				( $option == 'com_login' || $option == 'com_acctexp') ||
				( !$userid ) || ( $user->get('isRoot') )				// if user have root permission 
			) 
			{	return false; }
			
		return true;
	}
	
	/**
	 * Return user Profile
	 * @param $userid
	 */
	private static function _getProfile($userid)
	{
		//XiTODO:: Include appropriate location
		// include Community API
		require_once (JPATH_ROOT. DS.'components'.DS.'com_community'.DS.'libraries'.DS.'core.php');
		
		$pModel = CFactory::getModel('profile');
		$profile = $pModel->getEditableProfile($userid);
		return $profile;
	}

	/**
	 * Check redirection required or not as per plugin params
	 * @param $userid
	 * @param $required
	 */
	private static function _isRedirectRequired($userid, $required)
	{	
		$profile	= self::_getProfile($userid);
		$fields 	= $profile['fields'];
		
		foreach ($fields as $name => $fieldGroup) {
			foreach ($fieldGroup as $field) {
				if ( !$required || $field['required']) {
					if (empty($field['value'])) {
						return true;					// if we get any field empty
					}
				}
			}
		}
	
		return false;
	}
	
	/**
	 * Check component exist or not
	 * @param $comName
	 */
	private static function _isComponentExists($comName)
	{
		$comSitePath	=	JPATH_ROOT . DS . 'components' .DS.	'com_' . $comName;
		$comAdminPath	=	JPATH_ADMINISTRATOR . DS . 'components' .DS.	'com_' . $comName;
		
		if(!JFolder::exists($comSitePath) || !JFolder::exists($comAdminPath)) {
			return false;
		}
		
		return true;
	}
}
