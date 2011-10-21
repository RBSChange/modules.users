<?php
/**
 * users_DynamicgroupService
 * @package modules.users
 */
class users_DynamicgroupService extends users_GroupService
{
	/**
	 * @var users_DynamicgroupService
	 */
	private static $instance;

	/**
	 * @return users_DynamicgroupService
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
	 * @return users_persistentdocument_dynamicgroup
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/dynamicgroup');
	}

	/**
	 * Create a query based on 'modules_users/dynamicgroup' model.
	 * Return document that are instance of modules_users/dynamicgroup,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/dynamicgroup');
	}
	
	/**
	 * Create a query based on 'modules_users/dynamicgroup' model.
	 * Only documents that are strictly instance of modules_users/dynamicgroup
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_users/dynamicgroup', false);
	}

	/**
	 * @param users_persistentdocument_dynamicgroup $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$resume = parent::getResume($document, $forModuleName, $allowedSections);
		
		$keyPart = $document->getRefreshing() ? 'Yes' : 'No';
		$resume['properties']['refreshing'] = f_Locale::translateUI('&modules.generic.backoffice.'.$keyPart.';');
		
		try 
		{
			$reference = DocumentHelper::getDocumentInstance($document->getParameter('referenceId'));
			if ($reference !== null)
			{
				$resume['properties']['reference'] = $reference->getLabel() . ' (' . f_Locale::translateUI($reference->getPersistentModel()->getLabel()) . ' - ' . $reference->getId() . ')';
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			$resume['properties']['reference'] = f_Locale::translateUI('&modules.users.bo.doceditor.UNexisting-reference;');
		}
		
		return $resume;
	}

	/**
	 * @param users_persistentdocument_dynamicgroup $group
	 */
	public function refresh($group)
	{
		if (true || !$group->getRefreshing())
		{
			$this->getFeeder($group)->refreshUsers($group);
		}
	}
	
	/**
	 * @param users_persistentdocument_dynamicgroup $group
	 */
	public function getFeeder($group)
	{
		$className = $group->getClassName();
		if (f_util_ClassUtils::classExists($className) && f_util_ClassUtils::methodExists($className, 'getInstance'))
		{
			$feeder = f_util_ClassUtils::callMethod($className, 'getInstance');
			return $feeder;
		}
		else
		{
			throw new Exception('Invalid class: '.$className);
		}
	}
	
	/**
	 * @param users_persistentdocument_dynamicgroup $group
	 * @return Integer[]
	 */
	public function getUserIds($group)
	{
		return users_UserService::getInstance()->createQuery()
			->add(Restrictions::eq('groups', $group))
			->setProjection(Projections::property('id'))
			->findColumn('id');
	}
	
	/**
	 * @return users_persistentdocument_dynamicgroup[]
	 */
	public function getToRefresh()
	{
		return $this->createQuery()
			->add(Restrictions::eq('refreshing', false))
			->add(Restrictions::eq('autoRefresh', true))
			->find();
	}
	
	/* (non-PHPdoc)
	 * @see f_persistentdocument_DocumentService::addTreeAttributes()
	 */
	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
	{
		// TODO Auto-generated method stub
	    parent::addTreeAttributes($document, $moduleName, $treeType, $nodeAttributes);
	    $nodeAttributes['refreshing'] = f_Locale::translateUI('&modules.generic.backoffice.'.($document->getRefreshing() ? 'Yes' : 'No').';');
	    $nodeAttributes['autoRefresh'] = f_Locale::translateUI('&modules.generic.backoffice.'.($document->getAutoRefresh() ? 'Yes' : 'No').';');
	}

	
	
}