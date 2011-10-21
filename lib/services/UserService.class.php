<?php
class users_UserService extends f_persistentdocument_DocumentService
{
	const USER_LOGIN_EVENT = 'userLogin';
	const USER_LOGOUT_EVENT = 'userLogout';

	/**
	 * @var users_UserService
	 */
	private static $instance;
	
	/**
	 * @var boolean
	 */
	private $isLoginCaseSensitive;
	
	/**
	 * Returns the unique instance of UserService.
	 * @return users_UserService
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
	 * @return users_persistentdocument_user
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/user');
	}
	
	/**
	 * Are logins case sensitive. Default false starting with version 3.5.0.
	 * To enable login case sensitivity, turn to true "modules/users/loginCaseSensitive"
	 * configuration entry
	 * @return boolean
	 */
	public function isLoginCaseSensitive()
	{
		if ($this->isLoginCaseSensitive === null)
		{
			$this->isLoginCaseSensitive = Framework::getConfigurationValue("modules/users/loginCaseSensitive", false);
		}
		return $this->isLoginCaseSensitive;
	}

	/**
	 * Create a query based on 'modules_users/user' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/user');
	}

	/**
	 * @param users_persistentdocument_user $document
	 */
	protected function preDelete($document)
	{
		$currentUser = $this->getCurrentUser();
		if (DocumentHelper::equals($currentUser, $document))
		{
			throw new Exception('Can not delete current user');
		}

		if ($document->getIsroot())
		{
			if ($currentUser !== null && !$currentUser->getIsroot())
			{
				throw new Exception('Can not delete root user');
			}
		}
			
		$userQuery = generic_UserAclService::getInstance()->createQuery()
			->add(Restrictions::eq('user', $document))
			->delete();
			
		users_ProfileService::getInstance()->deleteProfilesByAccessorId($document->getId());
	}

	/**
	 * Add a functionality of send mail after the generic save
	 *
	 * @param users_persistentdocument_user $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 */
	protected function preSave($document, $parentNodeId)
	{
		$document->setInsertInTree(false);
		
		$email = $document->getEmail();
		if ($document->getLogin() == null)
		{
			$document->setLogin($email);
		}
		
		// The label is auto generated with login, first and last name.
		if ($document->getLabel() == null)
		{
			$document->setLabel($document->getLogin());
		}
		
		if ($document->getGeneratepassword() === "true")
		{
			$generatedPassword = $this->generatePassword();
			$document->setPassword($generatedPassword);
			$document->setPasswordmd5($this->encodePassword($generatedPassword));
			$document->setGeneratepassword(false);
		}
		else 
		{
			$password = $document->getClearPassword();
			if (f_util_StringUtils::isNotEmpty($password))
			{
				$document->setPasswordmd5($this->encodePassword($password));
			}
		}
		
		if (!$this->isLoginCaseSensitive())
		{
			$document->setLogin(f_util_StringUtils::strtolower($document->getLogin()));
		}
	}
	
	
	
	/**
	 * @param string $newLogin
	 * @param users_persistentdocument_user $user
	 * @param integer[] $groupIds
	 */
	public function validateUserLogin($newLogin, $user = null , $groupIds = array())
	{
		if (f_util_StringUtils::isEmpty($newLogin)) {return false;}
		
		if (!$this->isLoginCaseSensitive())
		{
			$newLogin = f_util_StringUtils::strtolower($newLogin);
		}
		
		$query = $this->createQuery()->add(Restrictions::eq('login', $newLogin));
		if ($user instanceof users_persistentdocument_user) 
		{
			$query->add(Restrictions::ne('id', $user->getId()));
		}
		if (count($groupIds))
		{
			$query->createCriteria('groups')->add(Restrictions::in('id', $groupIds));
		}
		$result = $query->setProjection(Projections::rowCount('count'))->find();
		if (is_array($result) && intval($result[0]['count']) > 0)
		{
			return false;
		}
		return true;
	}
	
	/**
	 * @param string $newLabel
	 * @param users_persistentdocument_user $user
	 * @param integer[] $groupIds
	 */
	public function validateUserLabel($newLabel, $user = null , $groupIds = array())
	{
		if (f_util_StringUtils::isEmpty($newLabel)) {return false;}		
		$query = $this->createQuery()->add(Restrictions::eq('label', $newLabel));
		if ($user instanceof users_persistentdocument_user) 
		{
			$query->add(Restrictions::ne('id', $user->getId()));
		}
		if (count($groupIds))
		{
			$query->createCriteria('groups')->add(Restrictions::in('id', $groupIds));
		}
		$result = $query->setProjection(Projections::rowCount('count'))->find();
		if (is_array($result) && intval($result[0]['count']) > 0)
		{
			return false;
		}
		return true;
	}

	/**
	 * @param users_persistentdocument_user $document
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
				throw new Exception('Can not update root user');
			}
		}
	}
	
	/**
	 * @param users_persistentdocument_user $document
	 * @param Integer $parentNodeId
	 */
	protected function postUpdate($document, $parentNodeId)
	{
		// If password has changed send email
		if (!is_null($document->getClearPassword()))
		{
			// Send the mail with new informations
			if ($this->sendUserInformations($document, false) !== true)
			{
				Framework::error('[UserService] Mail not send after reset of password for email: ' . $document->getEmail());
			}
			// Delete the clear password
			$document->resetClearPassword();
		}
	}
	
	/**
	 * @param users_persistentdocument_user $document
	 * @param integer $parentNodeId
	 */	
	protected function preInsert($document, $parentNodeId)
	{
		if ($parentNodeId > 0)
		{
			$parent = DocumentHelper::getDocumentInstance($parentNodeId);
			if ($parent instanceof users_persistentdocument_group) 
			{
				$document->addGroups($parent);
			}
		}
	}
	
	/**
	 * @param users_persistentdocument_user $document
	 * @param Integer $parentNodeId
	 */
	protected function postInsert($document, $parentNodeId)
	{
		// If password has changed send email
		if (!is_null($document->getClearPassword()))
		{
			// Send the mail with new informations
			if ($this->sendUserInformations($document, true) !== true)
			{
				Framework::error('[UserService] Mail not send after account creation for: ' . $document->getEmail());
			}
			// Delete the clear password
			$document->resetClearPassword();
		}
	}
	
	/**
	 * @param users_persistentdocument_user $document
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
	 * Moves $document into the destination node identified by $destId.
	 *
	 * @param users_persistentdocument_user $document The document to move.
	 * @param integer $destId ID of the destination node.
	 * @param integer $beforeId
	 * @param integer $afterId
	 */
	public function moveTo($document, $destId, $beforeId = null, $afterId = null)
	{
		$destDoc = DocumentHelper::getDocumentInstance($destId);
		if ($destDoc instanceof users_persistentdocument_group)
		{
			$document->addGroups($destDoc);
			$this->save($document);
		}
		elseif ($destDoc instanceof users_persistentdocument_nogroupuserfolder)
		{
			$document->removeAllGroups();
			$this->save($document);
		}
	}


	/**
	 * @param Integer[] $accessorIds
	 * @return Integer[]
	 */
	public function convertToUserIds($accessorIds)
	{
		if (f_util_ArrayUtils::isEmpty($accessorIds))
		{
			return array();
		}
		
		$ids1 = $this->createQuery()->add(Restrictions::in('id', $accessorIds))
			->setProjection(Projections::property('id'))
			->findColumn('id');
		if (count($accessorIds) === count($ids1))
		{
			return $ids1;
		}
		
		$ids2 = $this->createQuery()->add(Restrictions::in('groups.id', $accessorIds))->setProjection(Projections::groupProperty('id'))->findColumn('id');
		return array_unique(array_merge($ids1, $ids2));
	}
	
	/**
	 * @param Integer[] $accessorIds
	 * @return Integer[]
	 */
	public function convertToPublishedUserIds($accessorIds)
	{
		if (f_util_ArrayUtils::isEmpty($accessorIds))
		{
			return array();
		}
		
		$ids1 = $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::in('id', $accessorIds))
			->setProjection(Projections::property('id'))->findColumn('id');
		if (count($accessorIds) === count($ids1))
		{
			return $ids1;
		}
		
		$ids2 = $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::in('groups.id', $accessorIds))
			->setProjection(Projections::groupProperty('id'))->findColumn('id');
		return array_unique(array_merge($ids1, $ids2));
	}

	
	/**
	 * @param string $login
	 * @param users_persistentdocument_group $group
	 * @return users_persistentdocument_user[]
	 */
	public function getUsersByLoginAndGroup($login, $group = null)
	{
		if (f_util_StringUtils::isEmpty($login))
		{
			return array();
		}
		if (!$this->isLoginCaseSensitive())
		{
			$login = f_util_StringUtils::strtolower($login);
		}
		
		$query = $this->createQuery()->add(Restrictions::published())
					->add(Restrictions::eq('login', $login));
					
		if ($group instanceof users_persistentdocument_group)
		{
			$query->add(Restrictions::eq('groups', $group));
		}
		return $query->find();				
	}
	
	/**
	 * @param string $password
	 * @return string
	 */
	public function encodePassword($password)
	{
		return md5($password);
	}
	
	/**
	 * @param users_persistentdocument_user $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		return $document->getGroupsCount() > 0 && parent::isPublishable($document);
	}
	
	/**
	 * @param string $login
	 * @param string $password
	 * @param string $groupId
	 * @return users_persistentdocument_user || null
	 */
	public function getIdentifiedUser($login, $password, $groupId)
	{
		if (f_util_StringUtils::isEmpty($login) || f_util_StringUtils::isEmpty($password) || intval($groupId) <= 0)
		{
			return null;
		}
		
		if (!$this->isLoginCaseSensitive())
		{
			$login = f_util_StringUtils::strtolower($login);
		}
		
		$group = DocumentHelper::getDocumentInstance($groupId);
		users_GroupService::getInstance()->setDefaultGroup($group);
		
		$passwordMD5 = $this->encodePassword($password);
		$user = $this->createQuery()
			->add(Restrictions::eq('groups', $group))
			->add(Restrictions::eq('login', $login))
			->add(Restrictions::orExp(Restrictions::eq('passwordmd5', $passwordMD5), Restrictions::eq('changepasswordkey', $passwordMD5)))
			->add(Restrictions::published())
			->findUnique();
			
		if ($user instanceof users_persistentdocument_user)
		{
			$this->checkChangepasswordkey($user, $passwordMD5);	
		}
		return $user;
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param string $passwordMD5
	 * @return boolean
	 */
	protected function checkChangepasswordkey($user, $passwordMD5)
	{
		if ($user->getChangepasswordkey() === $passwordMD5)
		{
			$user->setPasswordmd5($passwordMD5);
			$user->setChangepasswordkey(null);
			$user->save();
			return true;
		}
		elseif ($user->getChangepasswordkey() !== null)
		{
			$user->setChangepasswordkey(null);
			$user->save();
			return true;
		}	
		return false;	
	}
	
	/**
	 * @param integer $groupId
	 * @return users_persistentdocument_user[]
	 */
	public function getRootUsersByGroupId($groupId)
	{
		return $this->createQuery()
			->add(Restrictions::eq('groups.id', $groupId))
			->add(Restrictions::eq('isroot', true))
			->find();
	}
	
	/**
	 * @return users_persistentdocument_user[]
	 */
	public function getRootUsers()
	{
		return $this->createQuery()->add(Restrictions::published())->add(Restrictions::eq('isroot', 1))->find();
	}
		
	/**
	 * Check if a given password match the password of a given user
	 * @param users_persistentdocument_user $user
	 * @param string $password
	 * @return boolean
	 */
	public function checkIdentity($user, $password)
	{
		if ($user instanceof users_persistentdocument_user && f_util_StringUtils::isNotEmpty($password))
		{
			$passwordMD5 = $this->encodePassword($password);
			return $user->isPublished() && $user->getPasswordmd5() === $passwordMD5;
		}
		return false;
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 */
	public function anonymize($user)
	{
		$userId = $user->getId();
		$user->setEmail(Framework::getConfigurationValue('modules/users/anonymousEmailAddress'));
		$user->setLogin('anonymous-'.$userId);
		$user->setPasswordmd5('anonymous');
		$user->removeAllGroups();
		$user->save();
		
		users_ProfileService::getInstance()->deleteProfilesByAccessorId($userId);
		
		$user->getDocumentService()->file($userId);
	}
		
	/**
	 * Generate a password to respond of a high security level define in change. Ex: hS2I7GF0r - number, letter in upper and lower case.
	 *
	 * @param int $length Define the length of the generated password
	 * @return string
	 */
	public function generatePassword($length = 10)
	{

		// Define the lists of characters
		$caracts = array(
		array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z"),
		array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"),
		array("0","1","2","3","4","5","6","7","8","9")
		);

		$lowerLetter = false;
		$upperLetter = false;
		$number = false;

		// Generate the n-2 first characters of password
		// When we add a type of character, we pass the good variable at true
		// so that the password contains three types.
		$tmpPassword = '';
		for ($i = 0; $i < $length-2; $i++)
		{
			$rand = rand(0,2);
			$tmpPassword .=  $caracts[$rand][rand(0,count($caracts[$rand])-1)];

			if ( $rand == 0 )
			{
				$lowerLetter = true;
			}
			else if ( $rand == 1 )
			{
				$upperLetter = true;
			}
			else
			{
				$number = true;
			}
		}

		// Test if the 3 differentes char are present
		if (! $lowerLetter )
		{
			$tmpPassword .=  $caracts[0][rand(0,count($caracts[0])-1)];
		}
		if ( ! $upperLetter )
		{
			$tmpPassword .=  $caracts[1][rand(0,count($caracts[1])-1)];
		}
		if ( ! $number )
		{
			$tmpPassword .=  $caracts[2][rand(0,count($caracts[2])-1)];
		}

		// If three types have been already present, complete password
		for ($i = 0; $i <= $length-strlen($tmpPassword); $i++)
		{
			$rand = rand(0,2);
			$tmpPassword .=  $caracts[$rand][rand(0,count($caracts[$rand])-1)];
		}

		// Return the password
		return $tmpPassword;

	}

	/**
	 * @param users_persistentdocument_user $user
	 */
	public function prepareNewPassword($user)
	{			
		try
		{
			$this->getTransactionManager()->beginTransaction();

			$newPassword = $this->generatePassword();
			$user->setChangepasswordkey($this->encodePassword($newPassword));
			$user->save();
			
			$ns = notification_NotificationService::getInstance();
			
			if ($this->getChangeUser()->getUserNamespace() == change_User::BACKEND_NAMESPACE)
			{
				$notifCode = 'modules_users/resetBackendUserPassword';
				$websiteId = null;
			}
			else
			{
				$notifCode = 'modules_users/resetFrontendUserPassword';
				$websiteId = website_WebsiteService::getInstance()->getCurrentWebsite()->getId();
			}
			
			$configuredNotif = $ns->getConfiguredByCodeName($notifCode, $websiteId);
			if ($configuredNotif instanceof notification_persistentdocument_notification)
			{
				$configuredNotif->setSendingModuleName('users');
				$callback = array($this, 'getNewPasswordNotifParamters');
				$params = array('user' => $user, 'password' => $newPassword, 'websiteId' => $websiteId);
				if (!$this->sendNotificationToUserCallback($configuredNotif, $user, $callback, $params))
				{
					throw new BaseException('Unable-to-send-password', 'modules.users.errors.Unable-to-send-password');
				}	
			}
			else 
			{
				throw new Exception('No published notification for code: ' . $notifCode);
			}
			
			$this->getTransactionManager()->commit();
			return $user;
		}
		catch (BaseException $e)
		{
			throw $e;
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
			throw new BaseException('Unable-to-generate-password', 'modules.users.errors.Unable-to-generate-password');
		}
		return null;
	}

	/**
	 * @param integer $groupId
	 * @return integer
	 */
	public function getCountByGroupId($groupId)
	{
		$rows = $this->createQuery()
			->add(Restrictions::eq('groups.id', $groupId))
			->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}

	/**
	 * @param integer $groupId
	 * @return integer
	 */	
	public function getPublishedCountByGroupId($groupId)
	{
		$rows = $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::eq('groups.id', $groupId))
			->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}
	
	/**
	 * @param integer $groupId
	 * @return integer
	 */	
	public function getInactiveCountByGroupId($groupId)
	{
		$rows = $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::eq('groups.id', $groupId))
			->add(Restrictions::isNull('lastping'))
			->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}
	
	
	/**
	 * @param date_Calendar $dateCalendarInstance
	 * @return Integer
	 */
	public function getInactiveSinceDateCountByGroupId($groupId, $dateCalendarInstance)
	{
		$rows = $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::eq('groups.id', $groupId))
			->add(Restrictions::orExp(Restrictions::isNull('lastping'), Restrictions::le('lastping', $dateCalendarInstance->toString())))
			->setProjection(Projections::rowCount("count"))->find();
		return $rows[0]['count'];
	}
	
	/**
	 * Reset a password for an user. If you pass the password, it's will be used else it's will be generated.
	 *
	 * @param users_persistentdocument_user $user
	 * @param string $password
	 *
	 * @throws IllegalArgumentException
	 */
	public function resetPassword($user, $password = null)
	{
		// If password does not passed, generate it
		if ($password === null || $password == '')
		{
			$password = $this->generatePassword();
		}

		// Set the password on user and save modification
		$user->setPassword($password);
		$this->save($user);
	}
	
	/**
	 * @param integer $groupId
	 * @param string $words
	 * @return f_persistentdocument_criteria_Query
	 */
	private function createSuableQuery($groupId, $words)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::published());
		$query->add(Restrictions::eq('groups.id', $groupId));
		if ($words != '')
		{
			foreach (explode(' ', $words) as $word)
			{
				$query->add(Restrictions::orExp(Restrictions::ilike('label', $word), Restrictions::ilike('email', $word)));
			}
		}
		return $query;
	}
	
	/**
	 * @param integer $groupId
	 * @param string $words
	 * @return integer
	 */
	public function getSuableCountByGroupId($groupId, $words = '')
	{
		$rows = $this->createSuableQuery($groupId, $words)->setProjection(Projections::rowCount('count'))->find();
		return $rows[0]['count'];
	}
	
	
	/**
	 * @param integer $groupId
	 * @param string $words
	 * @return users_persistentdocument_user[]
	 */
	public function getSuableByGroupId($groupId, $words = '', $firstResult = 0, $maxResult = -1)
	{
		return $this->createSuableQuery($groupId, $words)
			->setFirstResult($firstResult)
			->setMaxResults($maxResult)
			->addOrder(Order::iasc('label'))->find();
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @return boolean
	 */
	public function su($user)
	{
		// Check the current user.
		$oldUser = $this->getCurrentUser();
		if ($oldUser === null)
		{
			throw new Exception('Su can only be used by autenticated users!');
		}
		$sudoerStack = change_Controller::getInstance()->getStorage()->readForUser('users_sudoerStack'); 
		
		if (!is_array($sudoerStack) || count($sudoerStack) == 0)
		{
			if (!$oldUser->getIssudoer())
			{
				return false;
			}
			$sudoerStack = array();
		}
		
		// Store the current user id in order to set it back on logout.
		// Here we need to store the stack and set it after authentication 
		// because it clears the session.
		$sudoerStack[] = $oldUser->getId();
		
		// Authenticate as the user.
		$this->authenticate($user);
		
		// Set back the sudoer stack.
		change_Controller::getInstance()->getStorage()->writeForUser('users_sudoerStack', $sudoerStack);
		return true;
	}

	/**
	 * This method sends login information for a user, using the NotificationService.
	 *
	 * @param users_persistentdocument_user $user
	 * @param boolean $newAccount
	 * @return boolean
	 */
	public function sendUserInformations($user, $newAccount = true)
	{
		$strategyClassName = Framework::getConfigurationValue('modules/users/notificationStrategy', "users_DefaultUsersNotificationStrategy");
		$strategy =  new $strategyClassName();
		$code = $newAccount ? $strategy->getNewAccountNotificationCodeByUser($user) : $strategy->getPasswordChangeNotificationCodeByUser($user);
		if ($code === null)
		{
			return true;
		}
		
		$websiteId = $strategy->getNotificationWebsiteIdByUser($user);
		$configuredNotif = notification_NotificationService::getInstance()->getConfiguredByCodeName($code, $websiteId);
		if ($configuredNotif instanceof notification_persistentdocument_notification)
		{
			$configuredNotif->setSendingModuleName('users');
			$callback = array($this, 'getUserInformationNotifParameters');
			$params = array('user' => $user, 'code' => $code, 'strategy' => $strategy);
			$recipients = change_MailService::getInstance()->getRecipientsArray(array($user->getEmail()));
			return $this->sendNotificationToUserCallback($configuredNotif, $user, $callback, $params);
		}
		return true;
	}
	
	/**
	 * @param array $params
	 * @return $params
	 */
	public function getUserInformationNotifParameters($params)
	{
		return $params['strategy']->getNotificationSubstitutions($params['user'], $params['code']);
	}

	/**
	 * @return change_User
	 */
	protected function getChangeUser()
	{
		return change_Controller::getInstance()->getUser();
	}

	/**
	 * @param users_persistentdocument_user $user
	 */
	private function loginUser($user)
	{
		$now = date_Calendar::getInstance()->toString();
		$user->setLastlogin($now);
		$user->setLastping($now);
		$user->setMeta('modules.users.last-user-agent', $this->getUserAgent());
		$user->applyMetas();
				
		if ($user->isModified())
		{
			try
			{
				$this->getTransactionManager()->beginTransaction();
				$this->getPersistentProvider()->updateDocument($user);
				$this->getTransactionManager()->commit();
			}
			catch (Exception $e)
			{
				$this->getTransactionManager()->rollBack($e);
			}
		}
		
		if ($this->getChangeUser()->getUserNamespace() == change_User::BACKEND_NAMESPACE)
		{
			$action = 'login.backend';	
		}
		else 
		{
			$action = 'login.frontend';
		}
		
		$rq = RequestContext::getInstance();
		$params = array(
			'browsername' => f_Locale::translate('&modules.generic.browsers.' . $rq->getUserAgentType() . '_' . $rq->getUserAgentTypeVersion() . ';')
		);
		UserActionLoggerService::getInstance()->addCurrentUserDocumentEntry($action, $user, $params, 'users');

		f_event_EventManager::dispatchEvent(self::USER_LOGIN_EVENT, $this, array('user' => $user));
	}
	
	/**
	 * @return Sring
	 */
	private function getUserAgent()
	{
		if (isset($_SERVER['HTTP_USER_AGENT']))
		{
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
		}
		else
		{
			$userAgent = "";
		}
		return $userAgent;
	}

	/**
	 * @param users_persistentdocument_user $user
	 */
	private function logoutUser($user)
	{
		$user->setLastping(null);
		if ($user->isModified())
		{
			try
			{
				$this->getTransactionManager()->beginTransaction();
				$this->getPersistentProvider()->updateDocument($user);
				$this->getTransactionManager()->commit();
			}
			catch (Exception $e)
			{
				$this->getTransactionManager()->rollBack($e);
			}
		}
		f_event_EventManager::dispatchEvent(self::USER_LOGOUT_EVENT, $this, array('user' => $user));
	}

	/**
	 * @param users_persistentdocument_user $user
	 */
	public function authenticateBackEndUser($user)
	{
		$changeUser = $this->getChangeUser();
		if ($changeUser === null) {return;}

		$changeUser->setUserNamespace(change_User::BACKEND_NAMESPACE);
		$this->authenticate($user);
	}

	/**
	 * @param users_persistentdocument_user $user
	 */
	public function authenticateFrontEndUser($user)
	{
		$changeUser = $this->getChangeUser();
		if ($changeUser === null) {return;}

		$changeUser->setUserNamespace(change_User::FRONTEND_NAMESPACE);
		$this->authenticate($user);
	}
	
	/**
	 * set $user param to null for logout
	 * @param users_persistentdocument_user $user
	 */
	public function authenticate($user)
	{
		$changeUser = $this->getChangeUser();
		$oldUser = $this->getCurrentUser();
		
		if ($oldUser !== null)
		{		
			$this->logoutUser($oldUser);
			$changeUser->setAuthenticated(false);
			if (!DocumentHelper::equals($oldUser, $user))
			{
				$storage = change_Controller::getInstance()->getStorage();
				$sudoerStack = $storage->readForUser('users_sudoerStack'); 
				$defaultGroup = users_GroupService::getInstance()->getDefaultGroup();
			
				$storage->clearForUser();
				
				users_GroupService::getInstance()->setDefaultGroup($defaultGroup);				
				if ($user === null && is_array($sudoerStack) && count($sudoerStack) > 0)
				{
					$user = DocumentHelper::getDocumentInstance(array_pop($sudoerStack));
					$storage->writeForUser('users_sudoerStack', $sudoerStack);
				}
			}
		}

		if ($user instanceof users_persistentdocument_user)
		{
			$changeUser->setAuthenticated(true);
			$changeUser->setUser($user);
			$this->loginUser($user);
		}
		users_ProfileService::getInstance()->initCurrent(false);
	}

	/**
	 * 	@return users_persistentdocument_user
	 */
	protected function getUserFromSessionId($id)
	{
		$id = intval($id);
		if ($id > 0)
		{
			$modelName = $this->getPersistentProvider()->getDocumentModelName($id);
			if ($modelName !== null)
			{
				$user = $this->getDocumentInstance($id, $modelName);
				if ($user instanceof users_persistentdocument_user) 
				{
					return $user;
				}
			}
			change_Controller::getInstance()->getStorage()->clearForUser();
		}
		return null;
	}

	/**
	 * @return users_persistentdocument_user depending on the controller (ChangeController or XULController)
	 */
	public function getCurrentUser()
	{
		return $this->getUserFromSessionId($this->getChangeUser()->getId());	
	}
	
	/**
	 * @return integer 
	 */
	public function getAutenticatedUserId()
	{
		$id = intval($this->getChangeUser()->getId());
		return $id > 0 ? $id : users_AnonymoususerService::getInstance()->getAnonymousUserId();	
	}	
	
	/**
	 * @return users_persistentdocument_user 
	 */
	public function getAutenticatedUser()
	{
		$user = $this->getUserFromSessionId($this->getChangeUser()->getId());	
		return $user !== null ? $user : users_AnonymoususerService::getInstance()->getAnonymousUser();	
	}
	
	
	/**
	 * @return users_persistentdocument_user or null
	 */
	public function getCurrentBackEndUser()
	{
		$changeUser = $this->getChangeUser();
		$oldNameSpace = $changeUser->setUserNamespace(change_User::BACKEND_NAMESPACE);
		$id = $changeUser->getId();
		$currentUser = $this->getUserFromSessionId($id);
		$changeUser->setUserNamespace($oldNameSpace);
		return $currentUser;
	}

	/**
	 * @return users_persistentdocument_user or null
	 */
	public function getCurrentFrontEndUser()
	{
		$changeUser = $this->getChangeUser();
		$oldNameSpace = $changeUser->setUserNamespace(change_User::FRONTEND_NAMESPACE);
		$id = $changeUser->getId();
		$currentUser = $this->getUserFromSessionId($id);
		$changeUser->setUserNamespace($oldNameSpace);
		return $currentUser;
	}

	/**
	 * @param users_persistentdocument_user $user
	 */
	public function pingUser($user)
	{
		if ($user instanceof users_persistentdocument_user && 
			!($user instanceof users_persistentdocument_anonymoususer))
		{
			$user->setLastping(date_Calendar::getInstance()->toString());
			if ($user->isModified())
			{
				try
				{
					$this->getTransactionManager()->beginTransaction();
					$this->getPersistentProvider()->updateDocument($user);
					$this->getTransactionManager()->commit();
				}
				catch (Exception $e)
				{
					$this->getTransactionManager()->rollBack($e);
				}
			}
		}		
	}
	
	
	/**
	 * Ping current backend connected user
	 */
	public function pingBackEndUser()
	{
		$this->pingUser($this->getCurrentBackEndUser());
	}

	/**
	 * Get the proper "localized" salutation.
	 *
	 * @param users_persistentdocument_user $user
	 * @return string
	 */
	public function getLocalizedSalutation($user)
	{
		$title = '';

		$name = f_util_StringUtils::ucfirst($user->getFirstname()) . ' ' . f_util_StringUtils::ucfirst($user->getLastname());

		if (!$user->getTitle())
		{
			$localeKey = "&modules.users.frontoffice.salutation-undefined;";
		}
		else
		{
			$title = $user->getTitle()->getLabel();

			if (preg_match('/^(mm|mll|mad)/i', $title))
			{
				$localeKey = "&modules.users.frontoffice.salutation-woman;";
			}
			else
			{
				$localeKey = "&modules.users.frontoffice.salutation;";
			}
		}

		return f_Locale::translate($localeKey, array("title" => $title, "name"  => $name));
	}
	
	/**
	 * @see f_persistentdocument_DocumentService::getResume()
	 *
	 * @param users_persistentdocument_user $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		$lastLogin = $document->getUILastlogin();
		if ($lastLogin === null)
		{
			$lastLogin = f_Locale::translateUI("&modules.users.bo.doceditor.property.Lastlogin-empty;");
		}
		$data['history']['lastlogin'] = $lastLogin;
		$lastping = $document->getUILastping();
		if ($lastping !== null)
		{
			$data['history']['lastping'] = $lastping;
		}
		return $data;
	}
	
	/**
	 * @param notification_persistentdocument_notification $notification
	 * @param users_persistentdocument_user $user
	 * @param string $callback
	 * @param mixed $callbackParameter
	 * @return boolean
	 */
	public function sendNotificationToUserCallback($notification, $user, $callback = null, $callbackParameter = array())
	{
		if ($notification === null)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' No notification to send.');
			}
			return false;
		}
		else if ($user === null)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' No user to send notification.');
			}
			return false;
		}

		$recipients = change_MailService::getInstance()->getRecipientsArray(array($user->getLabel() => $user->getEmail()));
		$cb = array($this, 'getNotificationParametersCallback');
		$cbParams = array(
			'user' => $user,
			'callback' => $callback,
			'callbackParameter' => $callbackParameter
		);
		return $notification->getDocumentService()->sendNotificationCallback($notification, $recipients, $cb, $cbParams);
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param string $callback
	 * @param mixed $callbackParameter with keys 'user', 'callback' and 'callbackParameter'
	 * @return array
	 */
	public function getNotificationParametersCallback($params)
	{
		$replacements = $this->getNotificationParameters($params['user']);		
		if (isset($params['callback']) && $params['callback'])
		{
			$callbackReplacements = call_user_func($params['callback'], $params['callbackParameter']);
			if (is_array($callbackReplacements))
			{
				$replacements = array_merge($replacements, $callbackReplacements);
			}
		}			
		return $replacements;
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @return array
	 */
	public function getNotificationParameters($user)
	{
		$t = $user->getTitle();
		return array(
			'receiverFirstName' => $user->getFirstnameAsHtml(),
			'receiverLastName' => $user->getLastnameAsHtml(),
			'receiverFullName' => $user->getLabelAsHtml(),
			'receiverTitle' => ($t) ? $t->getLabelAsHtml() : '',
			'receiverEmail' => $user->getEmailAsHtml()
		);
	}
	
	/**
	 * @param array $params
	 * @return array
	 */
	public function getNewPasswordNotifParamters($params)
	{
		if (isset($params['websiteId']) && $params['websiteId'] > 0)
		{
			$accessLink = DocumentHelper::getDocumentInstance($params['websiteId'])->getUrl();
		}
		else
		{
			$accessLink = Framework::getUIBaseUrl();
		}		
		$user = $params['user'];
		return array(
			'login' => $user->getLoginAsHtml(),
			'password' => $params['password'],
			'accesslink' => $accessLink,
			'fullname' => $user->getFullnameAsHtml(),
			'ip' => RequestContext::getInstance()->getClientIp(),
			'date' => date_Formatter::toDefaultDateTime(date_Calendar::getUIInstance()) 
		);
	}
	
	/**
	 * @param users_persistentdocument_user $document
	 * @return integer | null
	 */
	public function getWebsiteId($document)
	{
		$profile = users_UsersprofileService::getInstance()->getByAccessorId($document->getId());
		if ($profile !== null)
		{
			$websiteId = $profile->getRegisteredwebsiteid();
			if ($websiteId && in_array($websiteId, $this->getWebsiteIds($document)))
			{
				return $websiteId;
			}
		}
		return null;
	}

	/**
	 * @param users_persistentdocument_user $document
	 * @return integer[] | null
	 */
	public function getWebsiteIds($document)
	{
		return website_WebsiteService::getInstance()->createQuery()
			->setProjection(Projections::groupProperty('id', 'id'))
			->add(Restrictions::eq('group.user', $document))
		->findColumn('id');
	}

	
	
	// Email confirmation.
	
	const EMAIL_CONFIRMATION_META_KEY = 'modules.users.email-confirmation-key';
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param boolean $isNew
	 * @param string $password
	 * @return boolean
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

			$configuredNotif->setSendingModuleName('users');
			$callback = array($this, 'getEmailConfirmationParameters');
			$params = array('user' => $user, 'key' => $userKey, 'password' => $password);
			return $this->sendNotificationToUserCallback($configuredNotif, $user, $callback, $params);
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
		/* @var $user users_persistentdocument_user */
		$emailConfirmUrl = LinkHelper::getActionUrl('users', 'ConfirmEmail', array('cmpref' => $user->getId(), 'key' => $params['key']));
		$t = $user->getTitle();
		$replacements = array(
			'email' => $user->getEmailAsHtml(), 
			'emailConfirmUrl' => $emailConfirmUrl,
			'login' => $user->getLoginAsHtml(),
			'password' => $params['password'],
			'fullname' => $user->getLabelAsHtml(),
			'title' => ($t) ? $t->getLabelAsHtml() : ''
		);
		return $replacements;
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param string $key
	 * @return Boolean
	 */
	public function confirmEmail($user, $key)
	{
		if ($user instanceof users_persistentdocument_user && $key == $user->getMeta(self::EMAIL_CONFIRMATION_META_KEY))
		{
			$status = $user->getPublicationstatus();
			if ($status === 'DRAFT' || $status === 'DEACTIVATED')
			{
				$user->setStartpublicationdate(date_Calendar::getInstance()->toString());
				$user->save();
				$user->activate();
				return $user->isPublished();
			}
		}
		return false;
	}
	
	
		
	// Deprecated.
	
	/**
	 * @param String $name
	 * @param array $arguments
	 */
	public function __call($name, $arguments)
	{
		switch ($name)
		{
			case 'getAgaviUser': 
				Framework::error('Call to deleted ' . get_class($this) . '->getAgaviUser method');
				try
				{
					return change_Controller::getInstance()->getContext()->getUser();
				}
				catch (Exception $e)
				{
					if (Framework::isInfoEnabled())
					{
						Framework::info(__METHOD__ . $e->getMessage());
					}
				}
				return null;
				
			case 'getGroupsByUser': 
				Framework::error('Call to deleted ' . get_class($this) . '->getGroupsByUser method');				
				if ($arguments[0] instanceof users_persistentdocument_user)
				{
					return $arguments[0]->getGroupsArray();
				}
				return array();
				
			case 'getFrontendUserByLogin':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');		
				return $this->deprecatedGetFrontendUserByLogin($arguments[0], isset($arguments[1]) ? $arguments[1] : null);
				
			case 'getBackEndUserByEmail':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');		
				return $this->deprecatedGetBackEndUserByEmail($arguments[0]);

			case 'getBackEndUserByLogin':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');		
				return $this->deprecatedGetBackEndUserByLogin($arguments[0]);

			case 'getIdentifiedFrontendUser':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');		
				return $this->deprecatedGetIdentifiedFrontendUser($arguments[0], $arguments[1], isset($arguments[2]) ? $arguments[2] : null);
			
			case 'getIdentifiedBackendUser':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');		
				return $this->deprecatedGetIdentifiedBackendUser($arguments[0], $arguments[1]);

			case 'isBackenduser':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');		
				return $this->deprecatedIsBackenduser($arguments[0]);
				
			case 'checkPassword':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');		
				return true;

			case 'sendNotificationToUser':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');		
				return $this->deprecatedSendNotificationToUser($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
			default: 
				return parent::__call($name, $arguments);
		}
	}
	
	/**
	 * @deprecated use sendNotificationToUserCallback
	 */
	public function deprecatedSendNotificationToUser($user, $notificationCode, $replacements, $senderModuleName)
	{
		$notification = notification_NotificationService::getInstance()->getByCodeName($notificationCode);
		if ($notification === null)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' No published notification for code "' . $notificationCode . '"');
			}
			return false;
		}		
		
		$replacements = array_merge($this->getNotificationParameters($user), $replacements);
		$receiverEmail = $user->getEmail();
		
		$ns = $notification->getDocumentService();
		$recipients = change_MailService::getInstance()->getRecipientsArray(array($receiverEmail));
		if (!$ns->send($notification, $recipients, $replacements, $senderModuleName))
		{
			Framework::warn(__METHOD__ . ' Can\'t send notification to ' . $receiverEmail);
			return false;
		}
		return true;
	}
	
	/**
	 * @deprecated
	 */
	private function deprecatedIsBackenduser($user)
	{
		if ($user instanceof users_persistentdocument_user)
		{
			return $user->getIndexofGroups(users_BackendgroupService::getInstance()->getBackendGroup()) != -1;
		}
		return false;
	}
	
	/**
	 * @deprecated
	 */
	private function deprecatedGetIdentifiedBackendUser($login, $password)
	{
		return $this->getIdentifiedUser($login, $password, users_BackendgroupService::getInstance()->getBackendGroupId());
	}
	
	/**
	 * @deprecated
	 */
	private function deprecatedGetIdentifiedFrontendUser($login, $password, $websiteId = null)
	{
		if (f_util_StringUtils::isEmpty($login) || f_util_StringUtils::isEmpty($password))
		{
			return null;
		}
		
		if (intval($websiteId) > 0)
		{
			$website = website_persistentdocument_website::getInstanceById(intval($websiteId));
			$groupId = $website->getGroup()->getId();
			$user =  $this->getIdentifiedUser($login, $password, $groupId);
			if ($user)
			{
				return $user;
			}
		}
		
		if (!$this->isLoginCaseSensitive())
		{
			$login = f_util_StringUtils::strtolower($login);
		}
		$passwordMD5 = $this->encodePassword($password);
		
		$backEndGroup = users_BackendgroupService::getInstance()->getBackendGroup();
		$users = $this->createQuery()
			->add(Restrictions::eq('login', $login))
			->add(Restrictions::orExp(Restrictions::eq('passwordmd5', $passwordMD5), Restrictions::eq('changepasswordkey', $passwordMD5)))
			->add(Restrictions::published())->find();
			
		foreach ($users as $user) 
		{
			/* @var $user users_persistentdocument_user  */
			if ($user->getGroupsCount() && $user->getIndexofGroups($backEndGroup) == -1)
			{
				$this->checkChangepasswordkey($user, $passwordMD5);
				return $user;
			}
		}
		return null;
	}
	
	/**
	 * @deprecated
	 */
	private function deprecatedGetBackEndUserByLogin($login)
	{
		if (f_util_StringUtils::isNotEmpty($login))
		{
			if (!$this->isLoginCaseSensitive())
			{
				$login = f_util_StringUtils::strtolower($login);
			}
			return $this->createQuery()
			->add(Restrictions::eq('groups.id', users_BackendgroupService::getInstance()->getBackendGroupId()))
			->add(Restrictions::eq('login', $login))
			->findUnique();
		}
		return null;
	}
	
	/**
	 * @deprecated
	 */
	private function deprecatedGetBackEndUserByEmail($email)
	{
		if (f_util_StringUtils::isNotEmpty($email))
		{
			return $this->createQuery()
				->add(Restrictions::eq('groups.id', users_BackendgroupService::getInstance()->getBackendGroupId()))
				->add(Restrictions::eq('email', $email))->find();
		}
		return array();
	}
	
	/**
	 * @deprecated
	 */
	private function deprecatedGetFrontendUserByLogin($login, $websiteId = null)
	{
		if (f_util_StringUtils::isNotEmpty($login))
		{
			if (!$this->isLoginCaseSensitive())
			{
				$login = f_util_StringUtils::strtolower($login);
			}
			
			if (intval($websiteId) > 0)
			{
				$website = website_persistentdocument_website::getInstanceById(intval($websiteId));
				$group = $website->getGroup();
				$user = $this->createQuery()
					->add(Restrictions::eq('groups', $group))
					->add(Restrictions::eq('login', $login))
					->findUnique();
					
				if ($user)
				{
					return $user;
				}
			}
			
			$backEndGroup = users_BackendgroupService::getInstance()->getBackendGroup();
				
			$users = $this->createQuery()->add(Restrictions::eq('login', $login))->find();
			foreach ($users as $user)
			{
				if ($user->getGroupsCount() && $user->getIndexofGroups($backEndGroup) == -1)
				{
					return $user;
				}
			}
		}
		return null;
	}
}