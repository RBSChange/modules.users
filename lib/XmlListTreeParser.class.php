<?php
class users_XmlListTreeParser extends tree_parser_XmlListTreeParser
{
    /**
     * This recursive method returns the XML tree data for the given level.
     *
     * @param Integer $nodeId ID of the root component from which the parsing should begin.
     * @param Integer $parentId Root component parent ID.
     * @param DOMElement $currentXmlDocRoot Current XML node.
     * @param Integer $level Tree's current level.
     * @param Boolean $virtualNode
     * @return DOMDocument XML tree data
     */
    protected function getTreeData($nodeId, $parentId = null, $currentXmlDocument = null, $level = 0, $firstCall = true)
    {
        if (is_null($this->xmlDoc))
        {
            $this->xmlDoc = $this->createXmldocRootElement();
            $currentXmlDocument = $this->getXmlDocRootNode();
        }
        try
        {
            $document = DocumentHelper::getDocumentInstance($nodeId);
            $currentNode = $this->createNodeFromDocument($document);
            $this->setXmlDocumentProperty($currentNode, 'label', $document->getLabel());
            
            if (! is_null($parentId))
            {
                $currentNode->setAttribute(self::ATTRIBUTE_PARENT_ID, $parentId);
            }
            $currentXmlDocument->appendChild($currentNode);
            if ($document instanceof generic_persistentdocument_folder)
            {
                $this->getTreeGroups($document, $currentNode);
            } 
            else if ($document instanceof users_persistentdocument_group)
            {
                $this->getTreeUsers($document, $currentNode);
            }
        } catch (Exception $e)
        {
            Framework::exception($e);
        }
        return $this->xmlDoc;
    }
    
    /**
     * @param generic_persistentdocument_folder $folder
     * @param DOMElement $parentDocument
     */
    private function getTreeGroups ($folder, $parentDocument)
    {
        $parentId = $folder->getId();
        if ($this->isDefinitionPointNode($parentId))
        {
            $parentDocument->setAttribute(self::ATTRIBUTE_HAS_PERMISSIONS, '1');
        }
        $permission = $this->getPermissionInfo($parentId);
        $parentDocument->setAttribute(self::ATTRIBUTE_PERMISSION, $permission);
        if ($permission == 'noaction')
        {
            return;
        }
        
        $groups = users_GroupService::getInstance()->createQuery()
            ->add(Restrictions::childOf($folder->getId()))->find();
        
        foreach ($groups as $group)
        {
            $currentNode = $this->createNodeFromDocument($group);
            $currentNode->setAttribute(self::ATTRIBUTE_PARENT_ID, $parentId);
            if ($this->isDefinitionPointNode($group->getId()))
            {
                $currentNode->setAttribute(self::ATTRIBUTE_HAS_PERMISSIONS, '1');
                $currentNode->setAttribute(self::ATTRIBUTE_PERMISSION, $this->getPermissionInfo($group->getId()));
            } else
            {
                $currentNode->setAttribute(self::ATTRIBUTE_PERMISSION, $permission);
            }
            $this->setXmlDocumentProperty($currentNode, 'label', $group->getLabel());
            $parentDocument->appendChild($currentNode);
        }
    }
    
    /**
     * @param users_persistentdocument_group $folder
     * @param DOMElement $parentDocument
     */
    private function getTreeUsers ($group, $parentDocument)
    {
        if ($this->filter && isset($this->filter['value']))
        {
            $filter = $this->filter['value'];
        } 
        else
        {
            $filter = null;
        }
        
        $parentId = $group->getId();
        $query = users_UserService::getInstance()->createQuery()->add(Restrictions::eq('groups.id', $parentId))->setProjection(Projections::rowCount('countItems'));
        $this->setQueryFilter($query, $filter);
        $resultCount = $query->find();
        $nbitems = intval($resultCount[0]['countItems']);
        $pageSize = $this->getLength();

        if ($nbitems <= 0)
        {
            return;
        }
        
        $pageTotal = intval($nbitems / $pageSize) + 1;
        if ($pageTotal > 1)
        {
            $parentDocument->setAttribute(self::ATTRIBUTE_PAGE_TOTAL, $pageTotal);
        }

        $start = $this->offset * $pageSize >= $nbitems ? 0 : $this->offset * $pageSize;
        $permission = $this->getPermissionInfo($parentId);
        $query = users_UserService::getInstance()->createQuery()
            ->add(Restrictions::eq('groups.id', $parentId))
            ->setFirstResult($start)->setMaxResults($pageSize);
            
        $this->setQueryFilter($query, $filter);
        $this->setQueryOrder($query, $this->getOrderColumn(), $this->getOrderDirection());
        $users = $query->find();
        
        $hasAttributeArray = isset($users[0]) && f_util_ClassUtils::methodExists($users[0], 'getTreeViewAttributeArray');
        
        foreach ($users as $user)
        {
            $currentNode = $this->createNodeFromDocument($user);
            $currentNode->setAttribute(self::ATTRIBUTE_PARENT_ID, $parentId);
            $currentNode->setAttribute(self::ATTRIBUTE_PERMISSION, $permission);
            $parentDocument->appendChild($currentNode);
            if ($hasAttributeArray)
            {
                foreach ($user->getTreeViewAttributeArray() as $name => $value) 
                {
                	$this->setXmlDocumentProperty($currentNode, $name, $value);
                }   
            }
        }
    }
    
    private function setXmlDocumentProperty ($xmlElement, $propertyName, $propertyValue)
    {
        $nodeAttribute = $xmlElement->ownerDocument->createElement('p', $propertyValue);
        $nodeAttribute->setAttribute(self::ATTRIBUTE_NAME, $propertyName);
        $xmlElement->appendChild($nodeAttribute);
    }
    
    /**
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 * @param f_persistentdocument_criteria_Query $query
	 * @param String[] $orders
	 */
	private function addOrders($documentModel, $query, $orders, $orderDirection)
	{
		$ordered = false;
		foreach ($orders as $propertyName)
		{
			$property = $documentModel->getProperty($propertyName);
			if ($property !== null)
			{
				if ($orderDirection == 'desc')
				{
					$query->addOrder(Order::desc($property->getDbMapping()));
				}
				else
				{
					$query->addOrder(Order::asc($property->getDbMapping()));
				}
				$ordered = true;
			}
		}

		if (!$ordered)
		{
			$query->addOrder(Order::desc('document_id'));
		}
	}

	/**
	 * @param f_persistentdocument_criteria_Query $query
	 * @param String $orderPropertyName
	 * @param String $orderDirection
	 */
	private function setQueryOrder ($query, $orderPropertyName, $orderDirection)
	{
		$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance('users', 'user');
		if ($orderPropertyName == "label")
		{
			$orders = array("firstname", "lastname");
		}
		elseif ($orderPropertyName)
		{
			$orders = array($orderPropertyName);
		}
		else
		{
			$orders = array();
		}
		$this->addOrders($documentModel, $query, $orders, $orderDirection);
	}
	
    /**
     * @param f_persistentdocument_criteria_Query $query
     * @param String $filterValue
     */
    private function setQueryFilter ($query, $filterValue)
    {
        if ($filterValue)
        {
            $query->add(Restrictions::orExp(Restrictions::ilike('login', $filterValue), Restrictions::ilike('firstname', $filterValue), Restrictions::ilike('lastname', $filterValue)));
        }
    }
    
    /**
     * @param DOMElement $currentNode
     * @param Integer $documentId
     * @param Boolean $virtualnode;
     */
    private function getPermissionInfo ($documentId)
    {
        $ps = f_permission_PermissionService::getInstance();
        if ($this->isDefinitionPointNode($documentId))
        {
        	$defPointId = $documentId;
        }
        else
        {
        	$defPointId = $ps->getDefinitionPointForPackage($documentId, 'modules_users');
        }
        if (!is_null($defPointId))
        {
            $permissions = $ps->getPermissionsForUserByDefPointNodeId(users_UserService::getInstance()->getCurrentBackEndUser(), $defPointId);
            if (count($permissions) == 1 && $permissions[0] == 'allpermissions')
            {
                return 'allpermissions';
            } 
            else if (count($permissions) == 0 || ! in_array('modules_users.List.rootfolder', $permissions))
            {
                return 'noaction';
            }
            $roleservice = $ps->getRoleServiceByModuleName('users');
            $names = $roleservice->getBackOfficeActions();
            foreach ($roleservice->getActionsByPermissions($permissions) as $actionName)
            {
                $backName = $roleservice->getBackOfficeActionName($actionName);
                $index = array_search($backName, $names);
                if ($index === false)
                {
                    $names[] = $backName;
                }
            }
            return implode(',', $names);
        } 
        else
        {
            return f_permission_PermissionService::ALL_PERMISSIONS;
        }
    }
}