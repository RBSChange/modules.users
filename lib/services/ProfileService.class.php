<?php
/**
 * @package modules.users
 * @method users_ProfileService getInstance()
 */
class users_ProfileService extends f_persistentdocument_DocumentService
{
	/**
	 * @return users_persistentdocument_profile
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/profile');
	}

	/**
	 * Create a query based on 'modules_users/profile' model.
	 * Return document that are instance of users_persistentdocument_profile,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_users/profile');
	}
	
	/**
	 * Create a query based on 'modules_users/profile' model.
	 * Only documents that are strictly instance of users_persistentdocument_profile
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_users/profile', false);
	}
	
	/**
	 * @param string $profilename
	 * @return users_ProfileService
	 */
	public function getServiceInstanceByName($profilename)
	{
		$serviceClassName = strtolower($profilename) . '_' . ucfirst($profilename) . 'profileService';
		if (!class_exists($serviceClassName))
		{
			throw new Exception($serviceClassName . ' Profile service class not found.');
		}
		return $serviceClassName::getInstance();
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		list($profilename, ) = explode('_', get_class($this));
		return $profilename;
	}
	
	
	/**
	 * @return string[]
	 */
	public function getProfileNames()
	{
		$result = array();
		$baseProfile = f_persistentdocument_PersistentDocumentModel::getInstance('users', 'profile');
		foreach ($baseProfile->getChildrenNames() as $modelName)
		{
			$info = f_persistentdocument_PersistentDocumentModel::getModelInfo($modelName);
			$result[] = $info['module'];
		}
		return $result;
	}
	
	/**
	 * 
	 * @param users_persistentdocument_user $accessor (or users_persistentdocument_group)
	 * @param string $profilename
	 * @return users_persistentdocument_profile
	 */
	public function createByAccessorAndName($accessor, $profilename)
	{
		$ps = $this->getServiceInstanceByName($profilename);
		$p = $ps->getByAccessorId($accessor->getId());
		if ($p === null)
		{
			$p = $ps->getNewDocumentInstance();
			$p->setAccessor($accessor);
		}
		return $p;
	}
	
	/**
	 * 
	 * @param integer $accessorId
	 * @return users_persistentdocument_profile[]
	 */
	public function getAllByAccessorId($accessorId)
	{
		$this->createQuery()->add(Restrictions::eq('accessorId', $accessorId))->find();
	}
	
	/**
	 * @param integer $accessorId
	 * @param string $profilename
	 * @return users_persistentdocument_profile
	 */
	public function getByAccessorIdAndName($accessorId, $profilename)
	{
		return $this->getServiceInstanceByName($profilename)->getByAccessorId($accessorId);
	}
	
	/**
	 * @param integer $accessorId
	 * @param string $profilename
	 * @return users_persistentdocument_profile
	 */
	public function getRequiredByAccessorIdAndName($accessorId, $profilename)
	{
		return $this->getServiceInstanceByName($profilename)->getByAccessorId($accessorId, true);
	}
	
	/**
	 * @param integer $accessorId
	 * @param boolean $required
	 * @return users_persistentdocument_profile
	 */
	public function getByAccessorId($accessorId, $required = false)
	{
		if ($this === users_ProfileService::getInstance())
		{
			return null;
		}
		$profile = $this->createQuery()->add(Restrictions::eq('accessorId', $accessorId))->findUnique();
		if ($profile === null && $required)
		{
			$profile = $this->getNewDocumentInstance();
			$profile->setAccessor(DocumentHelper::getDocumentInstance($accessorId));
		}
		return $profile;
	}
	
	/**
	 * @param string $profilename
	 * @return users_persistentdocument_profile
	 */
	public function getNewDocumentInstanceByName($profilename)
	{
		return $this->getServiceInstanceByName($profilename)->getNewDocumentInstance();
	}
	
	/**
	 * @param users_persistentdocument_profile $profile
	 * @param string $propertyName
	 * @return boolean
	 */
	public function hasProperty($profile, $propertyName)
	{
		if ($profile instanceof users_persistentdocument_profile)
		{
			return $profile->getPersistentModel()->getEditableProperty($propertyName) !== null;
		}
		return false;
	}
	
	/**
	 * @param users_persistentdocument_profile $profile
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getValue($profile, $propertyName)
	{
		if ($profile instanceof users_persistentdocument_profile)
		{
			$property = $profile->getPersistentModel()->getEditableProperty($propertyName);
			if ($property !== null)
			{
				$getter = 'get'. ucfirst($propertyName);
				if ($property->isArray())
				{
					$getter .= 'Array';
				}
				return $profile->{$getter}();
			}
		}
		return null;
	}
	
	/**
	 * @param boolean $ifNeeded
	 */
	public function initCurrent($ifNeeded = true)
	{
		if ($ifNeeded)
		{
			$profilValues = change_Controller::getInstance()->getStorage()->readForUser('profiles');
			if (!is_array($profilValues))
			{
				$this->loadFromSession();
			}
		}
		else
		{
			change_Controller::getInstance()->getStorage()->removeForUser('profiles');
			$this->loadFromSession();
		}
	}
			
	private function loadFromSession()
	{
		$profiles = change_Controller::getInstance()->getStorage()->readForUser('profiles');
		if (!is_array($profiles))
		{
			$accessorIds = array();
			$defaultGroup = users_GroupService::getInstance()->getDefaultGroup();
			if ($defaultGroup)
			{
				$accessorIds[] =  $defaultGroup->getId();
			}
			
			$user = users_UserService::getInstance()->getAutenticatedUser();
			if (!($user instanceof users_persistentdocument_anonymoususer))
			{
				array_unshift($accessorIds, $user->getId());
				$accessorIds[] = users_AnonymoususerService::getInstance()->getAnonymousUserId();
			}
			else
			{
				$accessorIds[] = $user->getId();
			}
			
			$profiles = array();
			$profilesvalues = array();
			
			foreach ($this->getProfileNames() as $profileName) 
			{
				$profiles[$profileName] = false;
				$sp = $this->getServiceInstanceByName($profileName);
				$profile = null;
				foreach ($accessorIds as $accessorId) 
				{
					$profile = $sp->getByAccessorId($accessorId);
					if ($profile !== null)
					{
						if ($profiles[$profileName] === false)
						{
							$profiles[$profileName] = $profile->getId();
						}
						
						$values = $sp->getSessionProperties($profile);
						foreach ($values as $n => $v) 
						{
							if ($v !== null && !isset($profilesvalues[$n])) {$profilesvalues[$n] = $v;}
						}
					}
				}
			}		
			change_Controller::getInstance()->getStorage()->writeForUser('profilesaccessorids', $accessorIds);	
			change_Controller::getInstance()->getStorage()->writeForUser('profiles', $profiles);
			change_Controller::getInstance()->getStorage()->writeForUser('profilesvalues', $profilesvalues);
		}
		return $profiles;
	}
	
	/**
	 * @param users_persistentdocument_profile $profile
	 * @return array
	 */
	protected function getSessionProperties($profile)
	{
		return array();
	}
	
	/**
	 * @var array<string => users_persistentdocument_profile>
	 */
	private $defaultCurrent = array();
	
	/**
	 * @param string $profilename
	 * @return users_persistentdocument_profile
	 */
	public function getCurrentByName($profilename)
	{
		$profiles = $this->loadFromSession();
		if (!isset($profiles[$profilename]))
		{
			throw new Exception('Invalid profile name: ' . $profilename);	
		}
		elseif ($profiles[$profilename] !== false)
		{
			return DocumentHelper::getDocumentInstance($profiles[$profilename]);
		}
		
		$profile = $this->getNewDocumentInstanceByName($profilename);
		$this->defaultCurrent[$profilename] = $profile;
		return $profile;
	}
	
	/**
	 * @param string $profilename
	 * @param string $propertyName
	 * @param boolean $useAncestor
	 * @return mixed
	 */
	public function getCurrentValue($profilename, $propertyName, $useAncestor = true)
	{
		$profiles = $this->loadFromSession();
		if (!isset($profiles[$profilename]))
		{
			throw new Exception('Invalid profile name: ' . $profilename);	
		}
		elseif ($profiles[$profilename] !== false)
		{
			$values = change_Controller::getInstance()->getStorage()->readForUser('profilesvalues');
			if (isset($values[$propertyName])) {return $values[$propertyName];}			
			$profile = DocumentHelper::getDocumentInstance($profiles[$profilename]);
			
			/* @var $profile users_persistentdocument_profile */
			$value = $this->getValue($profile, $propertyName);
			if ($value !== null || !$useAncestor)  {return $value;}

			$accessorIds = change_Controller::getInstance()->getStorage()->readForUser('profilesaccessorids');
			$index = array_search($profile->getAccessorId(), $accessorIds);
			if ($index !== false)
			{
				$sp = $this->getServiceInstanceByName($profilename);
				for ($i = $index + 1; $i < count($accessorIds); $i++) 
				{
					$profile = $sp->getByAccessorId($accessorIds[$i]);
					if ($profile !== null)
					{
						$value = $this->getValue($profile, $propertyName);
						if ($value !== null)
						{
							return $value;
						}
					}
				}
			}

		}
		return null;		
	}
	
	/**
	 * @param integer $accessorId
	 */
	public function deleteProfilesByAccessorId($accessorId)
	{
		$names = $this->getProfileNames();
		foreach ($names as $profilename)
		{
			$ps = $this->getServiceInstanceByName($profilename);
			$profile = $ps->getByAccessorId($accessorId);
			if ($profile !== null)
			{
				$ps->delete($profile);
			}
		}
	}
	
	/**
	 * @return users_persistentdocument_profile
	 */
	public function getCurrent()
	{
		if ($this === users_ProfileService::getInstance())
		{
			return null;
		}
		else
		{
			return users_ProfileService::getInstance()->getCurrentByName($this->getName());
		} 
	}
	
	/**
	 * @param users_persistentdocument_profile $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */	
	protected function preSave($document, $parentNodeId)
	{
		$document->setInsertInTree(false);
	}
}