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
		$document->setIsdefault(false);
		parent::preDelete($document);
		
		$wfus = users_WebsitefrontenduserService::getInstance();
		foreach($document->getUserArrayInverse() as $user)
		{
			$wfus->delete($user);
		}
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return users_persistentdocument_websitefrontendgroup
	 */
	public function getDefaultByUser($user)
	{
		$query = $this->createQuery()->add(Restrictions::eq('isdefault', true));
		$query->createCriteria('websitefrontenduser')->add(Restrictions::eq('id', $user->getId()));
		return $query->findUnique();
	}
		
	/**
	 * Get the default frontend Group
	 * @param website_persistentdocument_website $website
	 * @return users_persistentdocument_websitefrontendgroup
	 */
	public function getDefaultByWebsite($website)
	{
		return $this->getDefaultByWebsiteId($website->getId());
	}
	
	/**
	 * Get the default frontend Group
	 * @return users_persistentdocument_websitefrontendgroup
	 */
	public function getDefaultByWebsiteId($websiteId)
	{	
		$query = $this->createQuery()
			->add(Restrictions::eq('isdefault', true))
			->add(Restrictions::eq('websiteid', $websiteId));
		$group = $query->findUnique();
		
		if ($group === null)
		{
			$group = $this->createQuery()
				->add(Restrictions::eq('isdefault', true))
				->add(Restrictions::eq('linkedwebsites.id', $websiteId))->findUnique();
		}
		return $group;
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
			$this->createDefaultFromWebsite($website);	
		}
		else 
		{
			if ($group->getWebsiteid() == $website->getId())
			{
				$this->setDefaultLabel($group, $website);
				$this->save($group);
			}
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
	
	

	/**
	 * @param users_persistentdocument_websitefrontendgroup $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		parent::preSave($document, $parentNodeId);
		if ($document->isPropertyModified('linkedwebsites'))
		{
			$this->applyModifiedLinkedWebsites($document);
		}
	}
	
	/**
	 * @param users_persistentdocument_websitefrontendgroup $document
	 */
	protected function applyModifiedLinkedWebsites($document)
	{
		$result = array();
		foreach ($document->getLinkedwebsitesArray() as $doc) 
		{
			if ($doc instanceof users_persistentdocument_websitefrontendgroup)
			{
				if ($document->getWebsiteid() == $doc->getWebsiteid())
				{
					continue;
				}
				$website = DocumentHelper::getDocumentInstance($doc->getWebsiteid(), 'modules_website/website');
				$result[$website->getId()] = $website;
			}
			else if ($doc instanceof website_persistentdocument_website)
			{
				if ($document->getWebsiteid() == $doc->getId())
				{
					continue;
				}
				$result[$doc->getId()] = $doc;
			}
		}
		$document->setLinkedwebsitesArray(array_values($result));
	}
	
	/**
	 * @param integer $websiteId
	 * @return integer[]
	 */
	public function getLinkedWebsiteIds($websiteId)
	{
		$result =  array();
		$group = $this->createQuery()
				->add(Restrictions::eq('isdefault', true))
				->add(Restrictions::eq('websiteid', $websiteId))->findUnique();
				
		if ($group === null)
		{
			$group = $this->createQuery()
				->add(Restrictions::eq('isdefault', true))
				->add(Restrictions::eq('linkedwebsites.id', $websiteId))->findUnique();
		}
		
		if ($group === null)
		{
			Framework::warn(__METHOD__ . ' not default group for website id: '  .$websiteId);
			$result[] = $websiteId;
		}
		else
		{
			$result[] = $group->getWebsiteid();
			foreach ($group->getLinkedwebsitesArray() as $websiste) 
			{
				$result[] = $websiste->getId();
			}
		}
		return $result;
	}
	
	/**
	 * @param users_persistentdocument_websitefrontendgroup $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postSave($document, $parentNodeId)
	{
		parent::postSave($document, $parentNodeId);
		
		$query = $this->createQuery();
		$query->createCriteria('linkedwebsites')
			->setProjection(Projections::property('id', 'websiteid'));
			
		foreach ($query->find() as $row) 
		{
			$group = $this->createQuery()->add(Restrictions::eq('isdefault', true))
				->add(Restrictions::eq('websiteid', $row['websiteid']))->delete();
		}
	
		foreach (website_WebsiteService::getInstance()->getAll() as $website) 
		{
			$group = $this->getDefaultByWebsite($website);
			if ($group === null)
			{
				$this->createDefaultFromWebsite($website);
			}
		}
	}
	
	
	
	/**
	 * @see users_GroupService::getByLabel()
	 */
	public function getByLabel($label)
	{
		// TODO Auto-generated method stub
		
	}

	/**
	 * @see f_persistentdocument_DocumentService::getResume()
	 *
	 * @param users_persistentdocument_websitefrontendgroup $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		$website = DocumentHelper::getDocumentInstance($document->getWebsiteid());
		$data['properties']['website'] = $website->getUrl();
		if ($document->getLinkedwebsitesCount())
		{
			$linkedWebsites = array();
			foreach ($document->getLinkedwebsitesArray() as $website) 
			{
				$linkedWebsites[] = $website->getUrl();
			}
			$data['properties']['linkedWebsites'] = implode(" \n", $linkedWebsites);
		}
		return $data;
	}

	// Deprecated methods.
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return users_persistentdocument_websitefrontendgroup
	 * @deprecated use getDefaultByWebsite()
	 */
	public function getByWebsite($website)
	{
		return $this->getDefaultByWebsite($website);
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