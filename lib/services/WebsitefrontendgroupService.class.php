<?php
/**
 * @author inthause
 * @package modules.users
 */
class users_WebsitefrontendgroupService extends users_FrontendgroupService
{
	/**
	 * @var users_WebsitefrontendgroupService
	 */
	private static $instance;

	/**
	 * @return users_WebsitefrontendgroupService
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
	 * @return users_persistentdocument_websitefrontendgroup
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/websitefrontendgroup');
	}

	/**
	 * Create a query based on 'modules_users/websitefrontendgroup' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/websitefrontendgroup');
	}

	/**
	 * Create a query based on 'modules_users/websitefrontendgroup' model.
	 * Only documents that are strictly instance of modules_users/websitefrontendgroup
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_users/websitefrontendgroup', false);
	}
	
	/**
	 * @param users_persistentdocument_websitefrontendgroup $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		parent::preDelete($document);
		$wfus = users_WebsitefrontenduserService::getInstance();
		foreach($document->getUserArrayInverse() as $user)
		{
			$wfus->delete($user);
		}
	}
	
	/**
	 * Get the default frontend Group
	 * @return users_persistentdocument_websitefrontendgroup
	 */
	public function getDefaultByWebsite($website)
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('isdefault', true))
			->add(Restrictions::eq('websiteid', $website->getId()));
		return $query->findUnique();
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return users_persistentdocument_websitefrontendgroup[]
	 */
	public function getAllByWebsite($website)
	{
		return $this->createQuery()->add(Restrictions::eq('websiteid', $website->getId()))->find();
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return users_persistentdocument_websitefrontendgroup
	 */
	public function createDefaultFromWebsite($website)
	{
		if (!$website instanceof website_persistentdocument_website)
		{
			throw new IllegalArgumentException('website', 'website_persistentdocument_website');
		}
		$group = $this->getNewDocumentInstance();
		$group->setWebsiteid($website->getId());
		$group->setIsdefault(true);
		$this->setDefaultLabel($group, $website);
		$group->save(ModuleService::getInstance()->getRootFolderId('users'));
		return $group;
	}
	
	/**
	 * @param users_persistentdocument_websitefrontendgroup $group
	 * @param website_persistentdocument_website $website
	 */
	public function updateDefaultFromWebsite($website)
	{
		$group = $this->getDefaultByWebsite($website);
		if ($group === null)
		{
			$this->createFromWebsite($website);	
		}
		else 
		{
			$this->setDefaultLabel($group, $website);
			$this->save($group);
		}
	}
	
		/**
	 * @param users_persistentdocument_websitefrontendgroup $group
	 * @param website_persistentdocument_website $website
	 */
	private function setDefaultLabel($group, $website)
	{
		$group->setLabel(f_Locale::translate('&modules.users.document.websitefrontendgroup.Label-format;', array('website' => $website->getVoLabel())));
	}
	
	// Deprecated methods.
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return users_persistentdocument_websitefrontendgroup
	 * @deprecated use getDefaultByWebsite()
	 */
	public function getByWebsite($website)
	{
		return $this->createDefaultFromWebsite($website);
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @deprecated use createDefaultFromWebsite()
	 */
	public function createFromWebsite($website)
	{
		$this->createDefaultFromWebsite($website);
	}
	
	/**
	 * @param users_persistentdocument_websitefrontendgroup $group
	 * @param website_persistentdocument_website $website
	 * @deprecated use updateDefaultFromWebsite()
	 */
	public function updateFromWebsite($group, $website)
	{
		return $this->updateDefaultFromWebsite($website);
	}
}