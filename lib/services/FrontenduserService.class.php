<?php
class users_FrontenduserService extends users_UserService
{
	/**
	 * @var users_FrontenduserService
	 */
	private static $instance;

	/**
	 * Returns the unique instance of Frontenduser.
	 * @return users_FrontenduserService
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
	* @return users_persistentdocument_frontenduser
	*/
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/frontenduser');
	}

	/**
	 * Create a query based on 'modules_users/frontenduser' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/frontenduser');
	}
	
	/**
	 * Only documents that are strictly instance of modules_users/frontenduser
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_users/frontenduser', false);
	}

	/**
	 * @param users_persistentdocument_frontenduser $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		$login = $document->getLogin();
		if (empty($login))
		{
			$document->setLogin($document->getEmail());
		}
		parent::preSave($document, $parentNodeId);
	}

	/**
	 * @param users_persistentdocument_frontenduser $document
	 */
	protected function preDelete($document)
	{
		$currentUser = $this->getCurrentFrontEndUser();
		if (DocumentHelper::equals($currentUser, $document))
		{
			throw new Exception('Can not delete current user');
		}
		parent::preDelete($document);
	}

	/**
	 * @param users_persistentdocument_frontenduser $document
	 * @param Integer $parentNodeId
	 */
	protected function postInsert($document, $parentNodeId = null)
	{
		parent::postInsert($document, $parentNodeId);
		
		if (!$document instanceof users_persistentdocument_websitefrontenduser)
		{
			// Get the default frontend group
			$fgS = users_FrontendgroupService::getInstance();
			$defaultFrontendGroup = $fgS->getDefaultGroup();
	
			if ($defaultFrontendGroup instanceof users_persistentdocument_frontendgroup )
			{
				// Save the document in this group
				$defaultFrontendGroup->addUserInverse($document);
				$defaultFrontendGroup->save();
			}
		}
	}

	/**
	 * @param String $login
	 * @param Integer $websiteId
	 * @return users_persistentdocument_frontenduser
	 */
	public function prepareNewPassword($login, $websiteId)
	{
		try
		{
			$this->tm->beginTransaction();
			$user = $this->getFrontendUserByLogin($login, $websiteId);
			if ($user === null)
			{
				throw new BaseException('Invalid-login', 'modules.users.errors.Invalid-login');
			}
			$newPassword = $this->generatePassword();
			$user->setChangepasswordkey(md5($newPassword));
			$user->save();

			$ns = notification_NotificationService::getInstance();
			$configuredNotif = $ns->getConfiguredByCodeName('modules_users/resetFrontendUserPassword', $websiteId);
			if ($configuredNotif instanceof notification_persistentdocument_notification)
			{
				$configuredNotif->setSendingModuleName('users');
				$callback = array($this, 'getNewPasswordNotifParamters');
				$params = array('user' => $user, 'password' => $newPassword, 'websiteId' => $websiteId);
				$recipients = new mail_MessageRecipients($user->getEmail());
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
			$this->tm->rollBack($e);
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
	 * @param array $params
	 * @return array
	 */
	public function getNewpasswordNotifParamters($params)
	{
		$user = $params['user'];
	
		return array(
			'login' => $user->getLogin(),
			'password' => $params['password'],
			'accesslink' => Framework::getUIBaseUrl(),
			'fullname' => $user->getFullname(),
			'ip' => $_SERVER["REMOTE_ADDR"],
			'date' => date_Formatter::toDefaultDateTime(date_Calendar::getUIInstance()) 
		);
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
	
	// Email confirmation.
	
	const EMAIL_CONFIRMATION_META_KEY = 'modules.users.email-confirmation-key';
	
	/**
	 * @param users_persistentdocument_frontenduser $user
	 * @param Boolean $isNew
	 * @param String $password
	 * @return Boolean
	 */
	public function sendEmailConfirmationMessage($user, $isNew, $password = null)
	{
		$userKey = f_util_StringUtils::randomString();		
		$user->setMeta(self::EMAIL_CONFIRMATION_META_KEY, $userKey);
		$user->saveMeta();
				
		$ns = notification_NotificationService::getInstance();
		$notificationCode = 'modules_users/emailConfirmation' . ($isNew ? 'New' : 'Update');
		$configuredNotif = $ns->getConfiguredByCodeName($notificationCode);
		if ($configuredNotif instanceof notification_persistentdocument_notification)
		{
			$configuredNotif->setSendingModuleName('customer');
			$callback = array($this, 'getEmailConfirmationParameters');
			$params = array('user' => $user, 'key' => $userKey, 'password' => $password);
			return $user->getDocumentService()->sendNotificationToUserCallback($configuredNotif, $user, $callback, $params);
		}
		return false;
	}
	
	/**
	 * @param array $params
	 * @return array
	 */
	public function getEmailConfirmationParameters($params)
	{
		$user = $params['user'];
		$emailConfirmUrl = LinkHelper::getActionUrl('users', 'ConfirmEmail', array('cmpref' => $user->getId(), 'key' => $params['key']));
		$replacements = array(
			'email' => $user->getEmailAsHtml(), 
			'emailConfirmUrl' => $emailConfirmUrl,
			'login' => $user->getLoginAsHtml(),
			'password' => $params['password'],
			'fullname' => $user->getFullnameAsHtml(),
			'title' => ($user->getTitleId()) ? $user->getTitleidLabelAsHtml() : ''
		);
		return $replacements;
	}
	
	/**
	 * @param users_persistentdocument_frontenduser $user
	 * @param String $key
	 * @return Boolean
	 */
	public function confirmEmail($user, $key)
	{
		if ($user instanceof users_persistentdocument_frontenduser && $key == $user->getMeta(self::EMAIL_CONFIRMATION_META_KEY))
		{
			$status = $user->getPublicationstatus();
			if ($status === 'DRAFT' || $status === 'DEACTIVATED')
			{
				$this->activateFrontendUser($user);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @param users_persistentdocument_frontenduser $user
	 * @return Boolean
	 */
	private function activateFrontendUser($user)
	{
		if ($user instanceof users_persistentdocument_frontenduser && !$user->isPublished())
		{
			$user->setStartpublicationdate(date_Calendar::now()->toString());
			$user->save();
			$user->activate();
			return true;
		}
		return false;
	}
	
	private $currentUser = false;
	
	/**
	 * @return users_persistentdocument_frontenduser or null
	 */
	public function getCurrentUser()
	{
		if ($this->currentUser === false)
		{
			$agaviUser = $this->getAgaviUser();
			if ($agaviUser !== null)
			{
				$oldNameSpace = $agaviUser->setUserNamespace(FrameworkSecurityUser::FRONTEND_NAMESPACE);
				$id = $agaviUser->getId();

				$agaviUser->setUserNamespace($oldNameSpace);
				$this->currentUser = $this->getUserFromSessionId($id);
			}
			else
			{
				$this->currentUser = null;
			}
		}
		return $this->currentUser;
	}
}
