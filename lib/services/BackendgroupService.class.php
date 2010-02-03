<?php
class users_BackendgroupService extends users_GroupService
{
	/**
	 * @var users_BackendgroupService
	 */
	private static $instance;

	/**
	 * @return users_BackendgroupService
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
	 * @return users_persistentdocument_backendgroup
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/backendgroup');
	}

	/**
	 * Create a query based on 'modules_users/backendgroup' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/backendgroup');
	}
	
	/**
	 * Create a query based on 'modules_users/backendgroup' model.
	 * Only documents that are strictly instance of modules_users/backendgroup
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_users/backendgroup', false);
	}

	/**
	 * Get the default backend Group
	 * @return users_persistentdocument_backendgroup
	 */
	public function getDefaultGroup()
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('isdefault', true));
		return $query->findUnique();
	}
}