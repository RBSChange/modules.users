<?php
class users_WebsitefrontenduserService extends users_FrontenduserService
{
	/**
	 * @var users_WebsitefrontenduserService
	 */
	private static $instance;
	
	/**
	 * @return users_WebsitefrontenduserService
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
	 * @return users_persistentdocument_websitefrontenduser
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/websitefrontenduser');
	}
	
	/**
	 * Create a query based on 'modules_users/websitefrontenduser' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/websitefrontenduser');
	}
	
	/**
	 * Only documents that are strictly instance of modules_users/websitefrontenduser
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_users/websitefrontenduser', false);
	}
	
	
	/**
	 * @param users_persistentdocument_websitefrontenduser $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		parent::preInsert($document, $parentNodeId);
		if ($parentNodeId != null)
		{
			$websitefrontendgroup = DocumentHelper::getDocumentInstance($parentNodeId, 'modules_users/websitefrontendgroup');
		}
		
		$websiteid = $document->getWebsiteid();
		if ($websiteid == null && $parentNodeId == null)
		{
			throw new Exception('Invalid website');
		}
		else if ($websiteid == null)
		{
			$websiteid = $websitefrontendgroup->getWebsiteid();
		}
		
		$mastergroup = users_WebsitefrontendgroupService::getInstance()->getDefaultByWebsiteId($websiteid);
		$document->setWebsiteid($mastergroup->getWebsiteid());
	}
	
	/**
	 * @param users_persistentdocument_websitefrontenduser $user
	 * @param String $words
	 * @return f_persistentdocument_criteria_Query
	 */
	private function createSuableQuery($user, $words)
	{
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$query = $this->createQuery();
		$query->add(Restrictions::published());
		$query->add(Restrictions::eq('websiteid', $website->getId()));
		if ($words != '')
		{
			foreach (explode(' ', $words) as $word)
			{
				$query->add(Restrictions::orExp(Restrictions::ilike('label', $word), Restrictions::ilike('email', $word)));
			}
		}
		$query->add(Restrictions::eq('groups.sudoer', $user));
		
		return $query;
	}
	
	/**
	 * @param users_persistentdocument_websitefrontenduser $user
	 * @param String $words
	 * @return Integer
	 */
	public function getSuableCountByUser($user, $words = '')
	{
		$rows = $this->createSuableQuery($user, $words)->setProjection(Projections::rowCount('count'))->find();
		return $rows[0]['count'];
	}
	
	/**
	 * @param users_persistentdocument_websitefrontenduser $user
	 * @param String $words
	 * @return users_persistentdocument_websitefrontenduser[]
	 */
	public function getSuableByUser($user, $words = '', $firstResult = 0, $maxResult = -1)
	{
		return $this->createSuableQuery($user, $words)->setFirstResult($firstResult)->setMaxResults($maxResult)->addOrder(Order::iasc('lastname'))->addOrder(Order::iasc('firstname'))->find();
	}
	
	/**
	 * @param users_persistentdocument_websitefrontenduser $user
	 * @return Boolean
	 */
	public function su($user)
	{
		// Check the current user.
		$oldUser = $this->getCurrentFrontEndUser();
		if ($oldUser === null)
		{
			throw new Exception('Su can only be used by autenticated users!');
		}
		if (!$this->isSudoerForUser($oldUser, $user))
		{
			return false;
		}
		
		// Store the current user id in order to set it back on logout.
		// Here we need to store the stack and set it after authentication 
		// because it clears the session.
		$changeUser = change_Controller::getInstance()->getUser();
		$changeUser->setUserNamespace(change_User::FRONTEND_NAMESPACE);
		$sudoerStack = change_Controller::getInstance()->getStorage()->readForUser('users_sudoerStack');
		if (!is_array($sudoerStack))
		{
			$sudoerStack = array();
		}
		$sudoerStack[] = $oldUser->getId();
		
		// Authenticate as the user.
		$this->authenticateFrontEndUser($user);
		
		// Set back the sudoer stack.
		change_Controller::getInstance()->getStorage()->writeForUser('users_sudoerStack', $sudoerStack);
		return true;
	}
	
	/**
	 * @param users_persistentdocument_websitefrontenduser $suCandidate
	 * @param users_persistentdocument_websitefrontenduser $user
	 * @return Boolean
	 */
	private function isSudoerForUser($suCandidate, $user)
	{
		if (!$suCandidate->isPublished() || !$user->isPublished())
		{
			return false;
		}
		
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		if ($user->getWebsiteid() != $website->getId())
		{
			return false;
		}
		
		$query = $this->createQuery();
		$query->add(Restrictions::eq('id', $user->getId()));
		$query->add(Restrictions::eq('groups.sudoer', $suCandidate));
		if (!$query->findUnique())
		{
			return false;
		}
		
		return true;
	}
	/**
	 * @return Integer
	 */
	public function getCount($websiteId = null)
	{
		if ($websiteId === null)
		{
			return parent::getCount();
		}
		$rows = $this->createQuery()->add(Restrictions::eq('websiteid', $websiteId))->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}
	
	/**
	 * @return Integer
	 */
	public function getPublishedCount($websiteId = null)
	{
		if ($websiteId === null)
		{
			return parent::getPublishedCount();
		}
		$rows = $this->createQuery()->add(Restrictions::eq('websiteid', $websiteId))->add(Restrictions::published())->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}
	
	/**
	 * @return Integer
	 */
	public function getInactiveCount($websiteId = null)
	{
		if ($websiteId === null)
		{
			return parent::getInactiveCount();
		}
		$rows = $this->createQuery()->add(Restrictions::eq('websiteid', $websiteId))->add(Restrictions::published())->add(Restrictions::isNull('lastping'))->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}
	
	/**
	 * @param date_Calendar $dateCalendarInstance
	 * @return Integer
	 */
	public function getInactiveSinceDateCount($dateCalendarInstance, $websiteId = null)
	{
		if ($websiteId === null)
		{
			return parent::getInactiveSinceDateCount($dateCalendarInstance);
		}
		$rows = $this->createQuery()->add(Restrictions::eq('websiteid', $websiteId))->add(Restrictions::published())->add(Restrictions::orExp(Restrictions::isNull('lastping'), Restrictions::le('lastping', $dateCalendarInstance->toString())))->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}

}