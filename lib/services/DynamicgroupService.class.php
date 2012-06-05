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

		$ls = LocaleService::getInstance();
		$keyPart = $document->getRefreshing() ? 'yes' : 'no';
		$resume['properties']['refreshing'] = $ls->trans('m.generic.backoffice.'.$keyPart, array('ucf'));

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
			$resume['properties']['reference'] = $ls->trans('m.users.bo.doceditor.unexisting-reference', array('uc'));
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

	/**
	 * @param notification_persistentdocument_notification $document
	 * @param array<string, string> $attributes
	 * @param integer $mode
	 * @param string $moduleName
	 */
	public function completeBOAttributes($document, &$attributes, $mode, $moduleName)
	{
		parent::completeBOAttributes($document, $attributes, $mode, $moduleName);
		if ($mode & DocumentHelper::MODE_CUSTOM)
		{
			$ls = LocaleService::getInstance();
			$attributes['refreshing'] = $ls->trans('m.generic.backoffice.'.($document->getRefreshing() ? 'yes' : 'no'), array('ucf'));
			$attributes['autoRefresh'] = $ls->trans('m.generic.backoffice.'.($document->getAutoRefresh() ? 'yes' : 'no'), array('ucf'));
		}
	}
}