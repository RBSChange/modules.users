<?php
class users_FrontendgroupService extends users_GroupService
{
	/**
	 * @var users_FrontendgroupService
	 */
	private static $instance;

	/**
	 * @return users_FrontendgroupService
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
	 * @return users_persistentdocument_frontendgroup
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/frontendgroup');
	}

	/**
	 * Create a query based on 'modules_users/frontendgroup' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/frontendgroup');
	}

	/**
	 * Create a query based on 'modules_users/frontendgroup' model.
	 * Only documents that are strictly instance of modules_users/frontendgroup
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_users/frontendgroup', false);
	}
	
	/**
	 * Get the default frontend Group
	 * @return users_persistentdocument_frontendgroup
	 */
	public function getDefaultGroup()
	{
		// Do not return websitefrontentgroups.
		$query = $this->createStrictQuery()
			->add(Restrictions::eq('isdefault', true));
		return $query->findUnique();
	}
}