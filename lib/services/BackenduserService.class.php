<?php
class users_BackenduserService extends users_UserService
{
	/**
	 * @var users_BackenduserService
	 */
	private static $instance;

	/**
	 * Returns the unique instance of BackenduserService.
	 * @return users_BackenduserService
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
	 * @return users_persistentdocument_backenduser
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/backenduser');
	}

	/**
	 * Create a query based on 'modules_users/backenduser' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/backenduser');
	}

	/**
	 * @param users_persistentdocument_backenduser $document
	 * @param Integer $parentNodeId
	 */
	protected function postInsert($document, $parentNodeId = null)
	{
		parent::postInsert($document, $parentNodeId);

		// Get the default backend group
		$bgS = users_BackendgroupService::getInstance();
		$defaultBackendGroup = $bgS->getDefaultGroup();

		if ($defaultBackendGroup instanceof users_persistentdocument_backendgroup )
		{
			// Save the document in this group
			$defaultBackendGroup->addUserInverse($document);
			$defaultBackendGroup->save();
		}
	}

	/**
	 * @param users_persistentdocument_backenduser $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId = null)
	{
		if ($document->getIsroot())
		{
			$currentUser = $this->getCurrentBackEndUser();
			if ($currentUser !== null && !$currentUser->getIsroot())
			{
				throw new Exception('Can not update admin user');
			}
		}
		parent::preUpdate($document, $parentNodeId);
	}


	/**
	 * @param users_persistentdocument_backenduser $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		$currentUser = $this->getCurrentBackEndUser();
		if (DocumentHelper::equals($currentUser, $document))
		{
			throw new Exception('Can not delete current user');
		}

		if ($document->getIsroot())
		{
			if ($currentUser !== null && !$currentUser->getIsroot())
			{
				throw new Exception('Can not delete admin user');
			}
		}
		parent::preDelete($document);
	}

	/**
	 * @param users_persistentdocument_backenduser $document
	 * @param String $oldPublicationStatus
	 * @param array $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if ($document->getIsroot() && $document->getPublicationstatus() == 'DEACTIVATED')
		{
			$this->activate($document->getId());
		}
	}

	/**
	 * @return array<users_persistentdocument_backenduser>
	 */
	public function getRootUsers()
	{
		return $this->createQuery()->add(Restrictions::published())->add(Restrictions::eq('isroot', 1))->find();
	}

	/**
	 * @param String $login
	 * @return users_persistentdocument_backenduser
	 */
	public function prepareNewPassword($login)
	{
		try
		{
			$this->tm->beginTransaction();
			$user = $this->getBackEndUserByLogin($login);
			if ($user === null)
			{
				throw new BaseException('Invalid-login', 'modules.users.messages.error.LoginDoesNotExist');
			}
			elseif ($user->getIsroot())
			{
				throw new BaseException('Can-not-reset-admin-account', 'modules.users.messages.error.CanNotResetAdminAccount');
			}

			$newPassword = $this->generatePassword();
			$user->setChangepasswordkey(md5($newPassword));
			$user->save();
			
			$ns = notification_NotificationService::getInstance();
			$configuredNotif = $ns->getConfiguredByCodeName('modules_users/resetBackendUserPassword');
			if ($configuredNotif instanceof notification_persistentdocument_notification)
			{
				$configuredNotif->setSendingModuleName('users');
				$callback = array($this, 'getNewPasswordNotifParamters');
				$params = array('user' => $user, 'password' => $newPassword);
				if (!$user->getDocumentService()->sendNotificationToUserCallback($configuredNotif, $user, $callback, $params))
				{
					throw new BaseException('Unable-to-send-password', 'modules.users.errors.Unable-to-send-password');
				}	
			}
			else 
			{
				throw new Exception('No published notification for code "modules_users/resetBackendUserPassword"');
			}
			$this->tm->commit();
			return $user;
		}
		catch (BaseException $e)
		{
			throw $e;
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
			throw new BaseException('Unable-to-generate-password', 'modules.users.errors.Unable-to-generate-password');
		}
		return null;
	}
	
	/**
	 * @return Integer
	 */
	public function getCount()
	{
		$rows = $this->createQuery()->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}
	
	/**
	 * @return Integer
	 */
	public function getPublishedCount()
	{
		$rows = $this->createQuery()->add(Restrictions::published())->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}
	
	/**
	 * @return Integer
	 */
	public function getInactiveCount()
	{
		$rows = $this->createQuery()->add(Restrictions::published())->add(Restrictions::isNull('lastping'))->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}
	
	/**
	 * @param date_Calendar $dateCalendarInstance
	 * @return Integer
	 */
	public function getInactiveSinceDateCount($dateCalendarInstance)
	{
		$rows = $this->createQuery()->add(Restrictions::published())->add(Restrictions::orExp(Restrictions::isNull('lastping'), Restrictions::le('lastping', $dateCalendarInstance->toString())))->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}
	
	/**
	 * @return users_persistentdocument_backenduser or null
	 */
	public function getCurrentUser()
	{
		$changeUser = $this->getChangeUser();
		$oldNameSpace = $changeUser->setUserNamespace(change_User::BACKEND_NAMESPACE);
		$id = $changeUser->getId();
		$currentUser = $this->getUserFromSessionId($id);
		$changeUser->setUserNamespace($oldNameSpace);
		return $currentUser;
	}
}
