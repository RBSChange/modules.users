<?php
/**
 * users_ListDateformatService
 * @package modules.users.lib.services
 */
class users_ListDateformatService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @var users_ListDateformatService
	 */
	private static $instance;

	/**
	 * @return users_ListDateformatService
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
		$values = array('d/m/Y', 'Y-m-d', 'd.m.Y');
		if (Framework::inDevelopmentMode()) {$values[] = '{d|m|Y}';}
		$date = date_Converter::convertDateToLocal(date_Calendar::getInstance());
		foreach ($values as $value) 
		{
			$items[] = new list_Item(date_Formatter::format($date, $value), $value);
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
		$date = date_Converter::convertDateToLocal(date_Calendar::getInstance());
		return new list_Item(date_Formatter::format($date, $value), $value);
	}
}