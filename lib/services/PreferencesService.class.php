<?php
/**
 * @date Wed Feb 28 17:30:03 CET 2007
 * @author INTcoutL
 */
class users_PreferencesService extends f_persistentdocument_DocumentService
{
	/**
	 * @var users_PreferencesService
	 */
	private static $instance;

	/**
	 * @return users_PreferencesService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return users_persistentdocument_preferences
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/preferences');
	}

	/**
	 * Create a query based on 'modules_users/group' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/group');
	}


	/**
	 * @param users_persistentdocument_preferences $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		$document->setLabel('&modules.users.bo.general.Module-name;');
	}
}