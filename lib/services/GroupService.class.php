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
			self::$instance = self::getServiceClassInstance(get_class());
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
		if ($document->getIsdefault())
		{
			throw new Exception('Cannot_delete_default_group');
		}
		$groupQuery = $this->pp->createQuery('modules_generic/groupAcl')
			->add(Restrictions::eq('group', $document->getId()));
		$groupResults = $groupQuery->find();
		foreach ($groupResults as $acl)
		{
			$acl->delete();
		}
	}

	/**
	 * @param String $label
	 * @return users_persistentdocument_group or null
	 */
	public function getByLabel($label)
	{
		return $this->createQuery()->add(Restrictions::eq('label', $label))->findUnique();
	}
	
	// Deprecated methods.
	
	/**
	 * @param String $groupName
	 * @return users_persistentdocument_group or null
	 * @deprecated use getByLabel()
	 */
	public final function getGroupByName($groupName)
	{
		return $this->getByLabel($groupName);
	}
	

	/**
	 * @see f_persistentdocument_DocumentService::getResume()
	 *
	 * @param users_persistentdocument_group $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections)
	{
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		$data['properties']['cardinality'] = strval($document->getUserCountInverse());
		return $data;
	}
}