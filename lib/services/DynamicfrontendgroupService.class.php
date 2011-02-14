<?php
/**
 * users_DynamicfrontendgroupService
 * @package modules.users
 */
class users_DynamicfrontendgroupService extends users_FrontendgroupService
{
	/**
	 * @var users_DynamicfrontendgroupService
	 */
	private static $instance;

	/**
	 * @return users_DynamicfrontendgroupService
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
	 * @return users_persistentdocument_dynamicfrontendgroup
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/dynamicfrontendgroup');
	}

	/**
	 * Create a query based on 'modules_users/dynamicfrontendgroup' model.
	 * Return document that are instance of modules_users/dynamicfrontendgroup,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/dynamicfrontendgroup');
	}
	
	/**
	 * Create a query based on 'modules_users/dynamicfrontendgroup' model.
	 * Only documents that are strictly instance of modules_users/dynamicfrontendgroup
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_users/dynamicfrontendgroup', false);
	}

	/**
	 * @param users_persistentdocument_dynamicfrontendgroup $document
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
	 * @param users_persistentdocument_dynamicfrontendgroup $group
	 */
	public function refresh($group)
	{
		if (true || !$group->getRefreshing())
		{
			$this->getFeeder($group)->refreshUsers($group);
		}
	}
	
	/**
	 * @param users_persistentdocument_dynamicfrontendgroup $group
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
	 * @param users_persistentdocument_dynamicfrontendgroup $group
	 * @return Integer[]
	 */
	public function getUserIds($group)
	{
		return users_FrontenduserService::getInstance()->createQuery()
			->add(Restrictions::eq('groups', $group))
			->setProjection(Projections::property('id'))->findColumn('id');
	}
	
	/**
	 * @return users_persistentdocument_dynamicfrontendgroup[]
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