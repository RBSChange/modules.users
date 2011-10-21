<?php
/**
 * users_ListLcidService
 * @package modules.users.lib.services
 */
class users_ListLcidService extends BaseService implements list_ListItemsService
{
	/**
	 * @var users_ListLcidService
	 */
	private static $instance;

	/**
	 * @return users_ListLcidService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @see list_persistentdocument_dynamiclist::getItems()
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$items = array();
		
		$langs = RequestContext::getInstance()->getSupportedLanguages();
		foreach ($langs as $lang) 
		{
			$label = Framework::getConfigurationValue('languages/' . $lang . '/label', $lang);
			$lcid = LocaleService::getInstance()->getLCID($lang);
			$items[] = new list_Item($label, $lcid);
		}		
		
		return $items;
	}

	/**
	 * @var Array
	 */
	private $parameters = array();
	
	/**
	 * @see list_persistentdocument_dynamiclist::getListService()
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}
}