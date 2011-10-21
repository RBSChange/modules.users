<?php
/**
 * users_AnonymoususerService
 * @package modules.users
 */
class users_AnonymoususerService extends users_UserService
{
	/**
	 * @var users_AnonymoususerService
	 */
	private static $instance;

	/**
	 * @return users_AnonymoususerService
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
	 * @return users_persistentdocument_anonymoususer
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/anonymoususer');
	}

	/**
	 * Create a query based on 'modules_users/anonymoususer' model.
	 * Return document that are instance of users_persistentdocument_anonymoususer,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_users/anonymoususer');
	}
	
	/**
	 * Create a query based on 'modules_users/anonymoususer' model.
	 * Only documents that are strictly instance of users_persistentdocument_anonymoususer
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_users/anonymoususer', false);
	}
	
	/**
	 * @var integer
	 */
	protected $anonymousUserId;
	
	/**
	 * @return integer
	 */
	public function getAnonymousUserId()
	{
		if ($this->anonymousUserId === null)
		{
			$row = $this->getPersistentProvider()
				->createQuery('modules_users/anonymoususer', false)
				->add(Restrictions::eq('login', 'Anonymous'))
				->add(Restrictions::eq('passwordmd5', 'Anonymous'))
				->setProjection(Projections::property('id', 'id'))
				->findUnique();
			if ($row)
			{
				$this->anonymousUserId = intval($row['id']);
			}
			else
			{
				$anonymousUser = $this->getNewDocumentInstance();
				$anonymousUser->setLabel('Anonymous');
				$anonymousUser->setLogin('Anonymous');
				$anonymousUser->setPasswordmd5('Anonymous');
				$anonymousUser->setEmail(Framework::getConfigurationValue('modules/users/anonymousEmailAddress'));
				$anonymousUser->save();
				$this->anonymousUserId = $anonymousUser->getId();
			}	
		}
		return $this->anonymousUserId;
	}
	
	/**
	 * @return users_persistentdocument_anonymoususer
	 */
	public function getAnonymousUser()
	{
		return $this->getDocumentInstance($this->getAnonymousUserId(), 'modules_users/anonymoususer');
	}	
	
	/**
	 * @param users_persistentdocument_anonymoususer $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		parent::preSave($document, $parentNodeId);
		if ($document->getGroupsCount())
		{
			$document->removeAllGroups();
		}
	}
	
	/**
	 * @param users_persistentdocument_anonymoususer $document
	 */
	public function getWebsiteId($document)
	{
		return null;
	}

	/**
	 * @param users_persistentdocument_anonymoususer $document
	 */
	public function getWebsiteIds($document)
	{
		return array();
	}
}