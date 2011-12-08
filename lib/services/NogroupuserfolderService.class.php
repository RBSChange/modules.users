<?php
/**
 * users_NogroupuserfolderService
 * @package modules.users
 */
class users_NogroupuserfolderService extends generic_FolderService
{
	/**
	 * @var users_NogroupuserfolderService
	 */
	private static $instance;

	/**
	 * @return users_NogroupuserfolderService
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
	 * @return users_persistentdocument_nogroupuserfolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/nogroupuserfolder');
	}

	/**
	 * Create a query based on 'modules_users/nogroupuserfolder' model.
	 * Return document that are instance of users_persistentdocument_nogroupuserfolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_users/nogroupuserfolder');
	}
	
	/**
	 * Create a query based on 'modules_users/nogroupuserfolder' model.
	 * Only documents that are strictly instance of users_persistentdocument_nogroupuserfolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_users/nogroupuserfolder', false);
	}
	
	/**
	 * @var integer
	 */
	protected $noGroupUserFolderId;
	
	/**
	 * @return integer
	 */
	public function getNoGroupUserFolderId()
	{
		if ($this->noGroupUserFolderId === null)
		{
			$row = $this->getPersistentProvider()->createQuery('modules_users/nogroupuserfolder', false)
				->setProjection(Projections::property('id', 'id'))
				->findUnique();			
			if ($row)
			{
				$this->noGroupUserFolderId = intval($row['id']);
			}
			else
			{
				$noGroupUserFolder = $this->getNewDocumentInstance();
				$noGroupUserFolder->setLabel('Users with no group');
				$noGroupUserFolder->save(ModuleService::getInstance()->getRootFolderId('users'));
				$this->noGroupUserFolderId = $noGroupUserFolder->getId();
			}	
		}
		return $this->noGroupUserFolderId;
	}
	
	/**
	 * @return users_persistentdocument_nogroupuserfolder
	 */
	public function getNoGroupUserFolder()
	{
		return $this->getDocumentInstance($this->getNoGroupUserFolderId(), 'modules_users/nogroupuserfolder');
	}
	
	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param string[] $subModelNames
	 * @param integer $locateDocumentId null if use startindex
	 * @param integer $pageSize
	 * @param integer $startIndex
	 * @param integer $totalCount
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function getVirtualChildrenAt($document, $subModelNames, $locateDocumentId, $pageSize, &$startIndex, &$totalCount)
	{
		if ($locateDocumentId !== null)
		{
			$startIndex = 0;
			
			$idsArray = users_UserService::getInstance()->createQuery()
          			 ->add(Restrictions::isEmpty('groups'))
          			 ->addOrder(Order::asc('label'))
           		 ->setProjection(Projections::property('id', 'id'))->find(); 
           		          		 
           	$totalCount = count($idsArray);
           	foreach ($idsArray as $index => $row)
           	{            		
           		if ($row['id'] == $locateDocumentId)
           		{
           			$startIndex = $index - ($index % $pageSize);
           			break;
           		}
           	}	 
		}
		else
		{
			$countQuery = users_UserService::getInstance()->createQuery()
				 ->add(Restrictions::isEmpty('groups'))
					->setProjection(Projections::rowCount('countItems'));
      		$resultCount = $countQuery->find();
			$totalCount = intval($resultCount[0]['countItems']);
			Framework::info(__METHOD__  . "  $pageSize $startIndex $totalCount");
		}
		
		$query = users_UserService::getInstance()->createQuery()
          			 ->add(Restrictions::isEmpty('groups'))
          			 ->addOrder(Order::asc('label'))
           		 ->setFirstResult($startIndex)->setMaxResults($pageSize);
		return $query->find();
	}
}