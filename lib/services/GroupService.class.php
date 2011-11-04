<?php
class users_GroupService extends f_persistentdocument_DocumentService
{
	/**
	 * @var users_GroupService
	 */
	private static $instance;

	/**
	 * @return users_GroupService
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
	 * @return users_persistentdocument_group
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/group');
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
	 * @param users_persistentdocument_group $document
	 */
	protected function preDelete($document)
	{
		generic_GroupAclService::getInstance()->createQuery()
			->add(Restrictions::eq('group', $document))
			->delete();
	}

	/**
	 * @param String $label
	 * @return users_persistentdocument_group or null
	 */
	public function getByLabel($label)
	{
		return $this->createQuery()->add(Restrictions::eq('label', $label))->findUnique();
	}

	/**
	 * @see f_persistentdocument_DocumentService::getResume()
	 *
	 * @param users_persistentdocument_group $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		$data['properties']['cardinality'] = strval($document->getUserCountInverse());
		
		return $data;
	}
	
	/**
	 * @param filter_persistentdocument_queryfolder $document
	 * @param string[] $subModelNames
	 * @param integer $locateDocumentId null if use startindex
	 * @param integer $pageSize
	 * @param integer $startIndex
	 * @param integer $totalCount
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function getVirtualChildrenAt($document, $subModelNames, $locateDocumentId, $pageSize, &$startIndex, &$totalCount)
	{
		if ($locateDocumentId !== null)
		{
			$startIndex = 0;
			
			$idsArray = users_UserService::getInstance()->createQuery()
          			 ->add(Restrictions::eq('groups', $document))
          			 ->addOrder(Order::asc('label'))
           		 ->setProjection(Projections::property('id', 'id'))->find(); 
           		          		 
           	$totalCount = count($idsArray);
           	foreach ($idsArray as $index => $row)
           	{            		
           		if ($row['id'] == $locateDocumentId)
           		{
           			$startIndex = $index - ($index % $pageSize);
           			break;
           		}
           	}	 
		}
		else
		{
			$countQuery = users_UserService::getInstance()->createQuery()
				->add(Restrictions::eq('groups', $document))
				->setProjection(Projections::rowCount('countItems'));
      			$resultCount = $countQuery->find();
			$totalCount = intval($resultCount[0]['countItems']);
		}
		
		$query = users_UserService::getInstance()->createQuery()
          			 ->add(Restrictions::eq('groups', $document))
          			 ->addOrder(Order::asc('label'))
           		 ->setFirstResult($startIndex)->setMaxResults($pageSize);
		return $query->find();
	}
	
	/**
	 * @param users_persistentdocument_group $group
	 */
	public function setDefaultGroup($group)
	{
		if ($group instanceof users_persistentdocument_group)
		{
			$id = $group->getId();
			change_Controller::getInstance()->getStorage()->writeForUser('defaultGroup', $id);
		}
		else
		{
			change_Controller::getInstance()->getStorage()->removeForUser('defaultGroup');
		}
	}
	
	/**
	 * @return users_persistentdocument_group || null
	 */
	public function getDefaultGroup()
	{
		$id = change_Controller::getInstance()->getStorage()->readForUser('defaultGroup');
		if (intval($id) > 0)
		{
			return DocumentHelper::getDocumentInstance(intval($id));
		}
		return null;
	}
	
	/**
	 * @param String $name
	 * @param array $arguments
	 */
	public function __call($name, $arguments)
	{
		switch ($name)
		{
			case 'getDefaultByWebsite': 
				Framework::error('Call to deleted ' . get_class($this) . '->getDefaultByWebsite method');
				if ($arguments[0] instanceof website_persistentdocument_website) 
				{
					return $arguments[0]->getGroup();
				}
				return null;
			default: 
				return parent::__call($name, $arguments);
		}
	}
	
	/**
	 * @param users_persistentdocument_backendgroup $document
	 * @return null
	 */
	public function getWebsiteId($document)
	{
		return null;
	}

	/**
	 * @param users_persistentdocument_backendgroup $document
	 * @return integer[] or null
	 */
	public function getWebsiteIds($document)
	{
		return website_WebsiteService::getInstance()->createQuery()
			->setProjection(Projections::groupProperty('id', 'id'))
			->add(Restrictions::eq('group', $document))
		->findColumn('id');
	}
	
	public function getSecurityLevelArray()
	{
		return array('minimal', 'low', 'medium', 'high');
	}
	
	public function evaluateSecurityLevel($groups)
	{
		$_levelArray = $this->getSecurityLevelArray();
		if (is_array($groups))
		{
			$lvlIndex = null;
			foreach ($groups as $group) 
			{
				/* @var $group users_persistentdocument_group */
				$lvl = $group->getSecuritylevel();
				if (!empty($lvl))
				{
					$grpLvlIndex = array_search($lvl, $_levelArray);
					if ($grpLvlIndex !== false && ($lvlIndex === null || $grpLvlIndex < $lvlIndex))
					{
						$lvlIndex = $grpLvlIndex;
					}
				}
			}
			
			if ($lvlIndex !== null)
			{
				return $_levelArray[$lvlIndex];
			}
		}
		return $_levelArray[0];
	}
	//thod users_GroupService->getDefaultByWebsite
	
}