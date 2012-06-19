<?php
/**
 * @package modules.users
 * @method users_ListLcidService getInstance()
 */
class users_ListLcidService extends change_BaseService implements list_ListItemsService
{
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