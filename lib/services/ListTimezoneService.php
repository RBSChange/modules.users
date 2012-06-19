<?php
/**
 * users_ListTimezoneService
 * @package modules.users.lib.services
 */
class users_ListTimezoneService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @var users_ListTimezoneService
	 */
	private static $instance;

	/**
	 * @return users_ListTimezoneService
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
		
		foreach (DateTimeZone::listIdentifiers(DateTimeZone::EUROPE) as $tz) 
		{
			$items[] = new list_Item($tz, $tz);
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
	
	/**
	 * @see list_persistentdocument_dynamiclist::getItemByValue()
	 * @param string $value;
	 * @return list_Item
	 */
	public function getItemByValue($value)
	{
		return new list_Item($value, $value);
	}
}