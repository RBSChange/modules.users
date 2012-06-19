<?php
/**
 * @package modules.users
 * @method users_ListDatetimeformatService getInstance()
 */
class users_ListDatetimeformatService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @see list_persistentdocument_dynamiclist::getItems()
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$items = array();
		$values = array('d/m/Y H:i', 'Y-m-d g:i a', 'd.m.Y H:i');
		if (Framework::inDevelopmentMode()) {$values[] = '{d|m|Y H|i}';}
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