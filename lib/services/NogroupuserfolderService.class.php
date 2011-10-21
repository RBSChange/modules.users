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
	
	
	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
//	protected function preSave($document, $parentNodeId)
//	{
//		parent::preSave($document, $parentNodeId);
//
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preInsert($document, $parentNodeId)
//	{
//		parent::preInsert($document, $parentNodeId);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId)
//	{
//		parent::postInsert($document, $parentNodeId);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId)
//	{
//		parent::preUpdate($document, $parentNodeId);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postUpdate($document, $parentNodeId)
//	{
//		parent::postUpdate($document, $parentNodeId);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId)
//	{
//		parent::postSave($document, $parentNodeId);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
//		parent::preDelete($document);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
//		parent::preDeleteLocalized($document);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
//		parent::postDelete($document);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
//		parent::postDeleteLocalized($document);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
//	public function isPublishable($document)
//	{
//		$result = parent::isPublishable($document);
//		return $result;
//	}


	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
//	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
//	{
//		parent::publicationStatusChanged($document, $oldPublicationStatus, $params);
//	}

	/**
	 * Correction document is available via $args['correction'].
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Array<String=>mixed> $args
	 */
//	protected function onCorrectionActivated($document, $args)
//	{
//		parent::onCorrectionActivated($document, $args);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
//		parent::tagAdded($document, $tag);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
//		parent::tagRemoved($document, $tag);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//		parent::tagMovedFrom($fromDocument, $toDocument, $tag);
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param users_persistentdocument_nogroupuserfolder $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedTo($fromDocument, $toDocument, $tag)
//	{
//		parent::tagMovedTo($fromDocument, $toDocument, $tag);
//	}

	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
//	protected function onMoveToStart($document, $destId)
//	{
//		parent::onMoveToStart($document, $destId);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
//		parent::onDocumentMoved($document, $destId);
//	}

	/**
	 * this method is call before saving the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param users_persistentdocument_nogroupuserfolder $newDocument
	 * @param users_persistentdocument_nogroupuserfolder $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//		throw new IllegalOperationException('This document cannot be duplicated.');
//	}

	/**
	 * this method is call after saving the duplicate document.
	 * $newDocument has an id affected.
	 * Traitment of the children of $originalDocument.
	 *
	 * @param users_persistentdocument_nogroupuserfolder $newDocument
	 * @param users_persistentdocument_nogroupuserfolder $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function postDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//	}

	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
//	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
//	{
//		return null;
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//		return parent::getWebsiteId($document);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @return integer[] | null
	 */
//	public function getWebsiteIds($document)
//	{
//		return parent::getWebsiteIds($document);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//		return parent::getDisplayPage($document);
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
//	public function getResume($document, $forModuleName, $allowedSections = null)
//	{
//		$resume = parent::getResume($document, $forModuleName, $allowedSections);
//		return $resume;
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
//	public function getSolrsearchResultItemTemplate($document, $bockName)
//	{
//		return array('module' => 'users', 'template' => 'Users-Inc-NogroupuserfolderResultDetail');
//	}

	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param users_persistentdocument_nogroupuserfolder $document
	 * @param String[] $propertiesName
	 * @param Array $datas
	 */
//	public function addFormProperties($document, $propertiesName, &$datas)
//	{
//	}
}