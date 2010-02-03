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
		$user = $this->getFrontendUserByLogin($login, $websiteId);
		if ($user === null)
		{
			throw new BaseException('Invalid-login', 'modules.users.errors.Invalid-login');
		}
		$newPassword = $this->generatePassword();
		try 
		{
			$user->setChangepasswordkey(md5($newPassword));
			$user->save();
			
			$notificationService = notification_NotificationService::getInstance();
			$notification = $notificationService->getNotificationByCodeName('modules_users/resetFrontendUserPassword');
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
			$notificationService->send($notification, $recipients, $replacementArray, 'users');
			return $user;		
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			throw new BaseException('Unable-to-generate-password', 'modules.users.errors.Unable-to-generate-password');
		}
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
	
}