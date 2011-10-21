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
			self::$instance = new self();
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
		return $this->getPersistentProvider()->createQuery('modules_users/backendgroup');
	}
	
	/**
	 * Create a query based on 'modules_users/backendgroup' model.
	 * Only documents that are strictly instance of modules_users/backendgroup
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_users/backendgroup', false);
	}
	
	/**
	 * @var integer
	 */
	protected $backendGroupId;
	
	/**
	 * @return integer
	 */
	public function getBackendGroupId()
	{
		if ($this->backendGroupId === null)
		{
			$row = $this->getPersistentProvider()
				->createQuery('modules_users/backendgroup', false)
				->setProjection(Projections::property('id', 'id'))
				->findUnique();
				
			if ($row)
			{
				$this->backendGroupId = intval($row['id']);
			}
			else
			{
				$backendGroup = $this->getNewDocumentInstance();
				$backendGroup->setLabel('Backoffice users');
				$backendGroup->save(ModuleService::getInstance()->getRootFolderId('users'));
				$this->backendGroupId = $backendGroup->getId();
			}	
		}
		return $this->backendGroupId;
	}
	
	/**
	 * @return users_persistentdocument_backendgroup
	 */
	public function getBackendGroup()
	{
		return $this->getDocumentInstance($this->getBackendGroupId(), 'modules_users/backendgroup');
	}
	
	/**
	 * @param users_persistentdocument_backendgroup $document
	 */
	public function getWebsiteIds($document)
	{
		return array();
	}
	
	/**
	 * Used for deprecated call function
	 */
	public function __call($name, $args)
	{
		switch ($name) 
		{
			case 'getDefaultGroup':
				Framework::error('Call to deleted ' . get_class($this) . "->$name method");
				return $this->getBackendGroup();	
			default: 
				return parent::__call($name, $args);
		}
	}
}