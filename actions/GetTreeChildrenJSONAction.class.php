<?php
/**
 * @package modules.users
 */
class users_GetTreeChildrenJSONAction extends generic_GetTreeChildrenJSONAction
{
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string[] $subModelNames
	 * @param string $propertyName
	 * @return array<f_persistentdocument_PersistentDocument>
	 */
	protected function getVirtualChildren($document, $subModelNames, $propertyName)
	{
		if ($document instanceof users_persistentdocument_group)
		{
			$locateDocument = $this->getLocateDocument();
			if ($locateDocument !== null)
			{
				$idsArray = users_UserService::getInstance()->createQuery()
           			 ->add(Restrictions::eq('groups.id', $document->getId()))
           			 ->addOrder(Order::asc('document_label'))
            		 ->setProjection(Projections::property('id', 'id'))->find(); 
            	foreach ($idsArray as $index => $row)
            	{            		
            		if ($row['id'] == $locateDocument)
            		{
            			$this->setStartIndex($index - $index%$this->getPageSize());
            			break;
            		}
            	}
            		 
			}
			$offset = $this->getStartIndex();
			$pageSize = $this->getPageSize();
			$countQuery = users_UserService::getInstance()->createQuery()->add(Restrictions::eq('groups.id', $document->getId()))->setProjection(Projections::rowCount('countItems'));
       		$resultCount = $countQuery->find();
			$this->setTotal(intval($resultCount[0]['countItems']));
			$query = users_UserService::getInstance()->createQuery()
           			 ->add(Restrictions::eq('groups.id', $document->getId()))
           			 ->addOrder(Order::asc('document_label'))
            		 ->setFirstResult($offset)->setMaxResults($pageSize);
			return $query->find();
		}
		else
		{
			return parent::getVirtualChildren($document, $subModelNames, $propertyName);
		}
	}
}
