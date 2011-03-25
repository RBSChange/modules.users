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
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			$user = $this->getFrontendUserByLogin($login, $websiteId);
			if ($user === null)
			{
				throw new BaseException('Invalid-login', 'modules.users.errors.Invalid-login');
			}
			$newPassword = $this->generatePassword();
			$user->setChangepasswordkey(md5($newPassword));
			$user->save();

			$notificationService = notification_NotificationService::getInstance();
			$notification = $notificationService->getByCodeName('modules_users/resetFrontendUserPassword');
			if ($websiteId > 0)
			{
				$accessLink = DocumentHelper::getDocumentInstance($websiteId)->getUrl();
			}
			else
			{
				$accessLink = Framework::getBaseUrl();
			}

			$replacementArray = array(
				'login' => $user->getLogin(),
				'password' => $newPassword,
				'accesslink' => $accessLink,
				'fullname' => $user->getFullname(),
				'ip' => $_SERVER["REMOTE_ADDR"],
				'date' => date_DateFormat::format(date_Converter::convertDateToLocal(date_Calendar::now()))
			);

			$recipients = new mail_MessageRecipients();
			$recipients->setTo($user->getEmail());
			if (!$notificationService->send($notification, $recipients, $replacementArray, 'users'))
			{
				throw new BaseException('Unable-to-send-password', 'modules.users.errors.Unable-to-send-password');
			}
			$tm->commit();
			return $user;
		}
		catch (BaseException $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
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
		try
		{
			$ns = notification_NotificationService::getInstance();
			$notificationCode = 'modules_users/emailConfirmation' . ($isNew ? 'New' : 'Update');
			$notification = $ns->getByCodeName($notificationCode);
			$emailConfirmUrl = LinkHelper::getActionUrl('users', 'ConfirmEmail', array('cmpref' => $user->getId(), 'key' => $userKey));
			$replacements = array(
				'email' => $user->getEmail(), 
				'emailConfirmUrl' => $emailConfirmUrl,
				'login' => $user->getLogin(),
				'password' => $password,
				'fullname' => $user->getFullname(),
				'title' => $user->getTitle() ? $user->getTitle()->getLabel() : ''
			);
			$recipients = new mail_MessageRecipients();
			$recipients->setTo($user->getEmail());
			return $ns->send($notification, $recipients, $replacements, 'users');
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		return false;
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
