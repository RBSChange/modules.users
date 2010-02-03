<?php
class users_ActionBase extends f_action_BaseAction
{
	
	/**
	 * Returns the users_PreferencesService to handle documents of type "modules_users/preferences".
	 *
	 * @return users_PreferencesService
	 */
	public function getPreferencesService()
	{
		return users_PreferencesService::getInstance();
	}
	
	/**
	 * Returns the users_BackendgroupService to handle documents of type "modules_users/backendgroup".
	 *
	 * @return users_BackendgroupService
	 */
	public function getBackendgroupService()
	{
		return users_BackendgroupService::getInstance();
	}
	
	/**
	 * Returns the users_BackenduserService to handle documents of type "modules_users/backenduser".
	 *
	 * @return users_BackenduserService
	 */
	public function getBackenduserService()
	{
		return users_BackenduserService::getInstance();
	}
	
	/**
	 * Returns the users_FrontendgroupService to handle documents of type "modules_users/frontendgroup".
	 *
	 * @return users_FrontendgroupService
	 */
	public function getFrontendgroupService()
	{
		return users_FrontendgroupService::getInstance();
	}
	
	/**
	 * Returns the users_GroupService to handle documents of type "modules_users/group".
	 *
	 * @return users_GroupService
	 */
	public function getGroupService()
	{
		return users_GroupService::getInstance();
	}
	
	/**
	 * Returns the users_FrontenduserService to handle documents of type "modules_users/frontenduser".
	 *
	 * @return users_FrontenduserService
	 */
	public function getFrontenduserService()
	{
		return users_FrontenduserService::getInstance();
	}
	
	/**
	 * Returns the users_UserService to handle documents of type "modules_users/user".
	 *
	 * @return users_UserService
	 */
	public function getUserService()
	{
		return users_UserService::getInstance();
	}
	
	/**
	 * Returns the users_WebsitefrontendgroupService to handle documents of type "modules_users/websitefrontendgroup".
	 *
	 * @return users_WebsitefrontendgroupService
	 */
	public function getWebsitefrontendgroupService()
	{
		return users_WebsitefrontendgroupService::getInstance();
	}
	
	/**
	 * Returns the users_WebsitefrontenduserService to handle documents of type "modules_users/websitefrontenduser".
	 *
	 * @return users_WebsitefrontenduserService
	 */
	public function getWebsitefrontenduserService()
	{
		return users_WebsitefrontenduserService::getInstance();
	}
	
}