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
			self::$instance = self::getServiceClassInstance(get_class());
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
		$userQuery = $this->pp->createQuery('modules_generic/userAcl')->add(Restrictions::eq('user', $document->getId()));
		$userResults = $userQuery->find();
		foreach ($userResults as $acl)
		{
			$acl->delete();
		}
	}

	/**
	 * Add a functionality of send mail after the generic save
	 *
	 * @param users_persistentdocument_user $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 */
	protected function preSave($document, $parentNodeId)
	{
		// The label is auto generated with login, first and last name.
		$document->setLabel( $document->getLogin() . ' - ' . ucfirst($document->getFirstname()) . ' ' . ucfirst($document->getLastname()) );
		if ($document->getGeneratepassword() === "true")
		{
			$generatedPassword = $this->generatePassword();
			$document->setPassword($generatedPassword);
			$document->setPasswordmd5(md5($generatedPassword));
			$document->setGeneratepassword(false);
		}
		else 
		{
			$password = $document->getClearPassword();
			if (f_util_StringUtils::isNotEmpty($password))
			{
				$document->setPasswordmd5(md5($password));
			}
		}
		
		if (!$this->isLoginCaseSensitive())
		{
			$document->setLogin(f_util_StringUtils::strtolower($document->getLogin()));
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
	 * @param Integer[] $accessorIds
	 * @return Integer[]
	 */
	public function convertToUserIds($accessorIds)
	{
		if (f_util_ArrayUtils::isEmpty($accessorIds))
		{
			return array();
		}
		
		$ids1 = $this->createQuery()->add(Restrictions::in('id', $accessorIds))->setProjection(Projections::property('id'))->findColumn('id');
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
		
		$ids1 = $this->createQuery()->add(Restrictions::published())->add(Restrictions::in('id', $accessorIds))->setProjection(Projections::property('id'))->findColumn('id');
		if (count($accessorIds) === count($ids1))
		{
			return $ids1;
		}
		
		$ids2 = $this->createQuery()->add(Restrictions::published())->add(Restrictions::in('groups.id', $accessorIds))->setProjection(Projections::groupProperty('id'))->findColumn('id');
		return array_unique(array_merge($ids1, $ids2));
	}

	/**
	 * @param users_persistentdocument_user $user
	 */
	public function getGroupsByUser($user)
	{
		if ($user !== null)
		{
			return $user->getGroupsArray();
		}
		return array();
	}

	/**
	 * Check if a given password match the password of a given user
	 * @param users_persistentdocument_user $user
	 * @param string $password
	 * @return boolean
	 */
	public function checkIdentity($user, $password)
	{
		if ($user instanceof users_persistentdocument_user)
		{
			return $user->isPublished() && $user->getPasswordmd5() == md5($password);
		}
		return false;
	}

	/**
	 * @param String $login
	 * @param String $password
	 * @return users_persistentdocument_backenduser
	 */
	public function getIdentifiedBackendUser($login, $password)
	{
		if (f_util_StringUtils::isEmpty($login) || f_util_StringUtils::isEmpty($password))
		{
			return null;
		}
		
		$isPasswordOk = false;
		try
		{
			$this->tm->beginTransaction();
			$user = $this->getBackEndUserByLogin($login);
			if ($user !== null && $user->isPublished())
			{
				$passwordMD5 = md5($password);
				if ($user->getPasswordmd5() === $passwordMD5)
				{
					if ($user->getChangepasswordkey() !== null)
					{
						$user->setChangepasswordkey(null);
						$user->save();
					}
					$isPasswordOk = true;
				}
				else if ($user->getChangepasswordkey() === $passwordMD5)
				{
					$user->setPasswordmd5($passwordMD5);
					$user->setChangepasswordkey(null);
					$user->save();
					$isPasswordOk = true;
				}
			}
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			throw $this->tm->rollBack($e);
		}

		if ($isPasswordOk)
		{
			return $user;
		}
		else 
		{
			Framework::warn(__METHOD__ . " (2) LOGIN: " . $login . " REMOTE_ADDR:" . $_SERVER["REMOTE_ADDR"]);
			return null;
		}
	}

	/**
	 * @param String $login
	 * @param String $password
	 * @param Integer $websiteId
	 * @return users_persistentdocument_frontenduser
	 */
	public function getIdentifiedFrontendUser($login, $password, $websiteId = null)
	{
		if (f_util_StringUtils::isEmpty($login) || f_util_StringUtils::isEmpty($password))
		{
			Framework::warn(__METHOD__ . " (1) WEBSITEID: " . $websiteId . " LOGIN: " . $login . " PASSWORD: " . $password . " REMOTE_ADDR:" . $_SERVER["REMOTE_ADDR"]);
			return null;
		}
		$isPasswordOk = false;
		try
		{
			$this->tm->beginTransaction();
			$user = $this->getFrontendUserByLogin($login, $websiteId);
			if ($user !== null && $user->isPublished())
			{
				$passwordMD5 = md5($password);
				if ($user->getPasswordmd5() === $passwordMD5)
				{
					if ($user->getChangepasswordkey() !== null)
					{
						$user->setChangepasswordkey(null);
						$user->save();
					}
					$isPasswordOk = true;
				}
				else if ($user->getChangepasswordkey() === $passwordMD5)
				{
	
					$user->setPasswordmd5($passwordMD5);
					$user->setChangepasswordkey(null);
					$user->save();
					$isPasswordOk = true;
				}
			}
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			throw $this->tm->rollBack($e);
		}
		if ($isPasswordOk)
		{
			return $user;
		}
		else 
		{
			Framework::warn(__METHOD__ . " (2) WEBSITEID: " . $websiteId . " LOGIN: " . $login . " PASSWORD: " . $password . " REMOTE_ADDR:" . $_SERVER["REMOTE_ADDR"]);
			return null;
		}
	}

	/**
	 * @param String $login
	 * @param String $passwordmd5
	 * @return users_persistentdocument_backenduser
	 */
	public function getIdentifiedBackendPortalUser($login, $passwordmd5)
	{
		if (f_util_StringUtils::isEmpty($login) || f_util_StringUtils::isEmpty($passwordmd5))
		{
			Framework::warn(__METHOD__ . " LOGIN: " . $login . " PASSWORDMD5: " . $passwordmd5 . " REMOTE_ADDR:" . $_SERVER["REMOTE_ADDR"]);
			return null;
		}
		$user = $this->getBackEndUserByLogin($login);
		if ($user !== null && $user->isPublished())
		{
			if ($user->getPasswordmd5() === $passwordmd5)
			{
				return $user;
			}
		}

		Framework::warn(__METHOD__ . " LOGIN: " . $login . " PASSWORDMD5: " . $passwordmd5 . " REMOTE_ADDR:" . $_SERVER["REMOTE_ADDR"]);
		return null;
	}

	/**
	 * Search in database a user with his login. If no user found return null.
	 *
	 * @param string $login
	 * @return users_persistentdocument_backenduser
	 */
	public function getBackEndUserByLogin($login)
	{
		// Check if User exist in database
		if (!empty($login))
		{
			if (!$this->isLoginCaseSensitive())
			{
				$login = f_util_StringUtils::strtolower($login);
			}
			return users_BackenduserService::getInstance()->createQuery()
				->add(Restrictions::eq('login', $login))->findUnique();
		}

		return null;
	}

	/**
	 * Search in database a user with his login. If no user found returns empty array.
	 *
	 * @param string $email
	 * @return users_persistentdocument_backenduser[]
	 */
	public function getBackEndUserByEmail($email)
	{
		// Check if User exist in database
		if (!empty($email))
		{
			return users_BackenduserService::getInstance()->createQuery()
					->add(Restrictions::eq('email', $email))->find();
		}
		return array();
	}

	/**
	 * Search in database for a user with his login. If no user found return null.
	 *
	 * @param string $login
	 * @param Integer $websiteId
	 * @return users_persistentdocument_frontenduser
	 */
	public function getFrontendUserByLogin($login, $websiteId = null)
	{
		if (!empty($login))
		{
			if (!$this->isLoginCaseSensitive())
			{
				$login = f_util_StringUtils::strtolower($login);
			}
			if (intval($websiteId) > 0)
			{
				$websiteIds = users_WebsitefrontendgroupService::getInstance()->getLinkedWebsiteIds($websiteId);
				foreach ($websiteIds as $websiteId) 
				{
					$user = users_WebsitefrontenduserService::getInstance()->createQuery()
						->add(Restrictions::eq('websiteid', $websiteId))
						->add(Restrictions::eq('login', $login))
						->findUnique();
					if ($user !== null)
					{
						return $user;
					}
				}
			}

			$users = users_FrontenduserService::getInstance()->createQuery()
				->add(Restrictions::eq('login', $login))
				->find();
			foreach ($users as $user)
			{
				if ($user instanceof users_persistentdocument_websitefrontenduser) {continue;}
				return $user;
			}
		}

		return null;
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
	 * Check if a string password is valid. Use informations of preference document if it exist.
	 *
	 * @param string $password
	 * @return boolean
	 *
	 * @throws IllegalArgumentException
	 */
	public function checkPassword($password)
	{
		// Init validator
		$passwordValidator = new validation_PasswordValidator();
		$errors = new validation_Errors();
		if ($passwordValidator->validate($password , $errors))
		{
			return true;
		}
		return false;
	}

	/**
	 * Check if a user is a backend user
	 *
	 * @param users_persistentdocument_user $user
	 * @return boolean
	 */
	public function isBackenduser($user)
	{
		return $user instanceof users_persistentdocument_backenduser;
	}

	/**
	 * This method sends login information for a user, using the NotificationService.
	 *
	 * @param users_persistentdocument_user $user
	 * @param boolean $newAccount
	 *
	 * @return boolean
	 */
	public function sendUserInformations($user, $newAccount = true)
	{
		$ns = notification_NotificationService::getInstance();
		
		$websiteId = null;
		$strategyClassName = Framework::getConfigurationValue('modules/users/notificationStrategy', "users_DefaultUsersNotificationStrategy");
		$strategy =  new $strategyClassName();
		// This will return the desired notification only if it is publicated.
		$code = $newAccount ? $strategy->getNewAccountNotificationCodeByUser($user) : $strategy->getPasswordChangeNotificationCodeByUser($user);
		$websiteId = $strategy->getNotificationWebsiteIdByUser($user);
		$replacementArray = $strategy->getNotificationSubstitutions($user, $code);
		$recipients = new mail_MessageRecipients();
		$recipients->setTo($user->getEmail());
		if ($code !== null)
		{
			return $ns->send($ns->getByCodeName($code, $websiteId), $recipients, $replacementArray, 'users');
		}
		return true;
	}


	/**
	 * @return FrameworkSecurityUser or null
	 */
	protected function getAgaviUser()
	{
		try
		{
			return Controller::getInstance()->getContext()->getUser();
		}
		catch (Exception $e)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . $e->getMessage());
			}
		}
		return null;
	}

	private function invalidateCache($user)
	{
		// invalidate cache
		if ($this->isBackenduser($user))
		{
			$this->currentBackendUser = false;
		}
		else
		{
			$this->currentFrontendUser = false;
		}
	}

	/**
	 * @param users_persistentdocument_user $user
	 */
	private function loginUser($user)
	{
		$this->invalidateCache($user);
		$now = date_Calendar::now()->toString();
		$user->setLastlogin($now);
		$user->setLastping($now);
		$user->setMeta('modules.users.last-user-agent', $this->getUserAgent());
		$user->applyMetas();		
		if ($user->isModified())
		{
			try
			{
				$this->tm->beginTransaction();
				$this->pp->updateDocument($user);
				$this->tm->commit();
			}
			catch (Exception $e)
			{
				$this->tm->rollBack($e);
			}
		}
		
		if ($user instanceof users_persistentdocument_frontenduser)
		{
			$action = 'login.frontend';	
		}
		else 
		{
			$action = 'login.backend';	
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
		$this->invalidateCache($user);
		$user->setLastping(null);
		if ($user->isModified())
		{
			try
			{
				$this->tm->beginTransaction();
				$this->pp->updateDocument($user);
				$this->tm->commit();
			}
			catch (Exception $e)
			{
				$this->tm->rollBack($e);
			}
		}
		f_event_EventManager::dispatchEvent(self::USER_LOGOUT_EVENT, $this, array('user' => $user));
	}

	/**
	 * @param users_persistentdocument_backenduser $user
	 */
	public function authenticateBackEndUser($user)
	{
		$agaviUser = $this->getAgaviUser();
		if ($agaviUser === null) {return;}

		$agaviUser->setUserNamespace(FrameworkSecurityUser::BACKEND_NAMESPACE);
		$this->authenticate($user);
	}

	/**
	 * @param users_persistentdocument_frontenduser $user
	 */
	public function authenticateFrontEndUser($user)
	{
		$agaviUser = $this->getAgaviUser();
		if ($agaviUser === null) {return;}

		$agaviUser->setUserNamespace(FrameworkSecurityUser::FRONTEND_NAMESPACE);
		$this->authenticate($user);
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 */
	public function authenticate($user)
	{
		$agaviUser = $this->getAgaviUser();
		if ($agaviUser === NULL) {return;}

		$sudoerStack = $agaviUser->getAttribute('sudoerStack');
		$oldUser = $this->getCurrentUser();
		if ($oldUser !== null)
		{
			$this->logoutUser($oldUser);
			$agaviUser->setAuthenticated(false);
			if (!DocumentHelper::equals($oldUser, $user))
			{
				$agaviUser->clearAttributes();
			}
		}

		// If there is no specified user take it from the sudoer stack.
		if ($user === null && is_array($sudoerStack) && count($sudoerStack) > 0)
		{
			$user = DocumentHelper::getDocumentInstance(array_pop($sudoerStack));
		}
		// Else clear the sudoer stack.
		else
		{
			$sudoerStack = array();
		}

		if ($user instanceof users_persistentdocument_user)
		{
			$agaviUser->setAuthenticated(true);
			$agaviUser->setUser($user);
			$this->loginUser($user);
			$agaviUser->setAttribute('sudoerStack', $sudoerStack);
		}
	}

	/**
	 * 	@return users_persistentdocument_user
	 */
	protected function getUserFromSessionId($id)
	{
		$id = intval($id);
		if ($id > 0)
		{
			return $this->createQuery()->add(Restrictions::eq('id', $id))->findUnique();
		}
		return null;
	}

	/**
	 * Retrieves the current FrameworkSecurityUser from the context.
	 * @return users_persistentdocument_user frontendUser or backendUser or null, depending on the controller (ChangeController or XULController)
	 */
	public function getCurrentUser()
	{
		$agaviUser = $this->getAgaviUser();
		if ($agaviUser !== null)
		{
			return $this->getUserFromSessionId($agaviUser->getId());
		}
		return null;
	}
	
	/**
	 * @return users_persistentdocument_backenduser or null
	 */
	public function getCurrentBackEndUser()
	{
		return users_BackenduserService::getInstance()->getCurrentUser();
	}

	/**
	 * @return users_persistentdocument_frontenduser or null
	 */
	public function getCurrentFrontEndUser()
	{
		return users_FrontenduserService::getInstance()->getCurrentUser();
	}

	public function pingBackEndUser()
	{
		$user = $this->getCurrentBackEndUser();
		if ($user !== null)
		{
			$user->setLastping(date_Calendar::now()->toString());
			if ($user->isModified())
			{
				try
				{
					$this->tm->beginTransaction();
					$this->pp->updateDocument($user);
					$this->tm->commit();
				}
				catch (Exception $e)
				{
					$this->tm->rollBack($e);
				}
			}
		}
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
		return $data;
	}
	
	/**
	 * @param notification_persistentdocument_notification $notification
	 * @param users_persistentdocument_user $user
	 * @param string $callback
	 * @param mixed $callbackParameter
	 * @return boolean
	 */
	public function sendNotificationToUserCallback($notification, $user, $callback = null, $callbackParameter = null)
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
		
		$recipients = new mail_MessageRecipients();
		$recipients->setTo($user->getEmail());
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
			$replacements = array_merge($replacements, call_user_func($params['callback'], $params['callbackParameter']));
		}			
		return $replacements;
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @return array
	 */
	public function getNotificationParameters($user)
	{
		return array(
			'receiverFirstName' => $user->getFirstnameAsHtml(),
			'receiverLastName' => $user->getLastnameAsHtml(),
			'receiverFullName' => $user->getFullnameAsHtml(),
			'receiverTitle' => ($user->getTitleid()) ? $user->getTitleidLabelAsHtml() : '',
			'receiverEmail' => $user->getEmailAsHtml()
		);
	}
		
	// Deprecated.
	
	/**
	 * @deprecated (will be removed in 4.0) use sendNotificationToUserCallback
	 */
	public function sendNotificationToUser($user, $notificationCode, $replacements, $senderModuleName)
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
		$recipients = new mail_MessageRecipients();
		$recipients->setTo($receiverEmail);
		if (!$ns->send($notification, $recipients, $replacements, $senderModuleName))
		{
			Framework::warn(__METHOD__ . ' Can\'t send notification to ' . $receiverEmail);
			return false;
		}
		return true;
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getIdentifiedBackendUser or getIdentifiedFrontendUser
	 */
	public final function checkIdentityByLogin($login, $password)
	{
		if (!is_null($user = $this->getUserByLogin($login)))
		{
			return $this->checkIdentity($user, $password);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getGroupsByUser
	 */
	public final function getGroupsForUser($login)
	{
		$user = $this->getUserByLogin($login);
		if ($user !== null)
		{
			return $user->getGroupsArray();
		}
		return array();
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getBackEndUserByLogin or getFrontendUserByLogin
	 */
	public final function getUserByLogin($login)
	{
		// Check if User exist in database
		if (!empty($login))
		{
			if (!$this->isLoginCaseSensitive())
			{
				$login = f_util_StringUtils::strtolower($login);
			}
			$query = $this->createQuery()->add(Restrictions::eq('login', $login));
			$users = $query->find();
			if (count($users) > 0)
			{
				if (count($users) > 1)
				{
					if (Framework::isWarnEnabled())
					{
						Framework::warn(__METHOD__ . ' return '. count($users) . ' possible users!!!');
						Framework::warn(f_util_ProcessUtils::getBackTrace());
					}
				}
				return $users[0];
			}
		}
		return null;
	}

	/**
	 * @deprecated (will be removed in 4.0) use getBackEndUserByLogin or getFrontendUserByLogin
	 */
	public final function loginExist($login)
	{
		return $this->getUserByLogin($login) !== null;
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use resetPassword
	 */
	public final function resetPasswordForLogin($login, $password = null)
	{
		$user = $this->getUserByLogin($login);
		if ($user === null)
		{
			throw new Exception('User not found for login: ' . $login);
		}

		// Call the method to reset password
		$this->resetPassword($user, $password);
	}
}