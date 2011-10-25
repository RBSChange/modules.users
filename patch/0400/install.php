<?php
/**
 * users_patch_0400
 * @package modules.users
 */
class users_patch_0400 extends change_Patch
{
	/**
	 * @return array
	 */
	public function getPreCommandList()
	{
		return array(
			array('disable-site'),
		);
	}
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->execChangeCommand('compile-documents');
		$this->execChangeCommand('compile-listeners');
		$this->execChangeCommand('compile-locales', array('users'));
		$this->execChangeCommand('generate-database', array('users'));
		$this->execChangeCommand('import-data', array('users', 'lists.xml'));
		$this->execChangeCommand('website.compile-blocks');
		
		try 
		{
			$sql = "ALTER TABLE `m_users_doc_group` ADD `securitylevel` varchar(25)";
			$this->executeSQLQ($sql);
			
			$sql = "ALTER TABLE `m_website_doc_website` ADD `group` int(11) default NULL";
			$this->executeSQLQ($sql);
			
			
			$sql = "ALTER TABLE `m_users_doc_user` ADD `issudoer` tinyint(1) NOT NULL default '0'";
			$this->executeSQLQ($sql);
		}
		catch (Exception $e)
		{
			//Already applied
		}
		
		$this->migrateSecurityLevel();
		
		while (true)
		{
			$query = "SELECT `document_id` FROM `m_users_doc_group` WHERE `document_modelversion` <> '4.0' LIMIT 0 , 1";
			$rows = $this->executeSQLSelect($query)->fetchAll(PDO::FETCH_ASSOC);
			if (!is_array($rows) || count($rows) == 0) {break;}
			foreach ($rows as $row) 
			{
				$this->log('--- Migrate Group: ' . $row['document_id']);
				$this->migrateGroup($row['document_id']);
			}
		}
		
		while (true)
		{
			$query = "SELECT `document_id` FROM `m_users_doc_user` WHERE `document_modelversion` <> '4.0' LIMIT 0 , 1";
			$rows = $this->executeSQLSelect($query)->fetchAll(PDO::FETCH_ASSOC);
			if (!is_array($rows) || count($rows) == 0) {break;}
			foreach ($rows as $row) 
			{
				$this->log('--- Migrate User: ' . $row['document_id']);
				$this->migrateUser($row['document_id']);
			}
		}
		
		$anonymousUser = users_AnonymoususerService::getInstance()->getAnonymousUser();
		$this->log('--- Anonymous User: ' . $anonymousUser->getId() . ' ' . $anonymousUser->getLabel());
		
		$backenGroupUser = users_BackendgroupService::getInstance()->getBackendGroup();
		$this->log('--- Backend Group: ' . $backenGroupUser->getId() . ' ' . $backenGroupUser->getLabel());
		
		$noGroupUserFolder = users_NogroupuserfolderService::getInstance()->getNoGroupUserFolder();
		$this->log('--- No Group User Folder: ' . $noGroupUserFolder->getId() . ' ' . $noGroupUserFolder->getLabel());
		
		
		$this->migrateWebsitesSecurity();
		
		$this->migrateDynamicGroup();
	}
	
	private function migrateWebsitesSecurity()
	{
		$query = "SELECT `document_id`, `group` FROM `m_website_doc_website`";
		$info = $this->executeSQLSelect($query)->fetchAll(PDO::FETCH_ASSOC);
		$ids = array(ModuleService::getInstance()->getRootFolderId('website'));
		
		$roleName = 'modules_website.AuthenticatedFrontUser';
		foreach ($info as $row) 
		{
			$groupId = intval($row['group']);
			if ($groupId)
			{
				$ids[] = intval($row['document_id']);
				$group = DocumentHelper::getDocumentInstance($groupId);
				change_PermissionService::getInstance()->addRoleToGroup($group, $roleName, array(intval($row['document_id'])));
			}
		}
		
		$anonymouseUser = users_AnonymoususerService::getInstance()->getAnonymousUser();
		change_PermissionService::getInstance()->addRoleToUser($anonymouseUser, $roleName, $ids);
	}
	
	private function migrateDynamicGroup()
	{
		$sql = "UPDATE `m_task_doc_plannedtask` SET `systemtaskclassname`= 'users_RefreshDynamicgroupTask'
 WHERE `systemtaskclassname` = 'users_RefreshDynamicfrontendgroupTask'";
		$this->executeSQLQ($sql);
		
		$sql = "UPDATE `m_task_doc_plannedtask` SET `systemtaskclassname`= 'users_RefreshDynamicgroupsTask'
 WHERE `systemtaskclassname` = 'users_RefreshDynamicfrontendgroupsTask'";
		$this->executeSQLQ($sql);
		
		$sql = "UPDATE `m_users_doc_group` SET `classname` = 'customer_GroupFeederService'
 WHERE `classname` = 'customer_FrontendgroupFeederService'";
		$this->executeSQLQ($sql);		
		
	}	
	private function migrateSecurityLevel()
	{
		$query = "SELECT `document_id`, `securitylevel` FROM `m_users_doc_preferences`";
		$rows = $this->executeSQLSelect($query)->fetchAll(PDO::FETCH_ASSOC);
		if (is_array($rows) && count($rows))
		{
			$securitylevel = $rows[0]['securitylevel'];
			$docId = $rows[0]['document_id'];
			$node = $this->getTreeNode($docId);
			
			$this->log('Remove user preference document '. $docId);
			
			if ($node)
			{
				TreeService::getInstance()->deleteNode($node);
			}
			$sql = "DELETE FROM `f_document` WHERE `document_id` = $docId";
			$this->executeSQLQ($sql);
		}
		else
		{
			$securitylevel = 'medium';
		}
		$this->log('Update security level to '. $securitylevel);
		$sql = "UPDATE `m_users_doc_group` SET `securitylevel`= '$securitylevel' WHERE `securitylevel` IS NULL";
		$this->executeSQLQ($sql);
	}
	
	private function getTreeNode($docId)
	{
		$query = "SELECT `treeid` FROM `f_document` WHERE `document_id` = $docId";
		$rows = $this->executeSQLSelect($query)->fetchAll(PDO::FETCH_ASSOC);
		if (is_array($rows) && count($rows) && !empty($rows[0]['treeid']))
		{
			$info = $this->getPersistentProvider()->getNodeInfo($docId, intval($rows[0]['treeid']));
			if ($info === null) {return null;}
			return f_persistentdocument_PersistentTreeNode::getInstance($info);
		}
		return null;
	}
	
	private function migrateGroup($groupId)
	{
		$query = "SELECT * FROM `m_users_doc_group` WHERE `document_id` = $groupId";
		$rows = $this->executeSQLSelect($query)->fetchAll(PDO::FETCH_ASSOC);
		$groupInfo = $rows[0];
		$updateRelations = true;
		if ($groupInfo['document_model'] === 'modules_users/dynamicfrontendgroup')
		{
			$groupInfo['document_model'] = 'modules_users/dynamicgroup';
		}
		elseif ($groupInfo['document_model'] === 'modules_users/frontendgroup')
		{
			$groupInfo['document_model'] = 'modules_users/group';
		}
		elseif ($groupInfo['document_model'] === 'modules_users/websitefrontendgroup')
		{
			$groupInfo['document_model'] = 'modules_users/group';
			
			//linkedwebsites, websiteid
			$websiteId = intval($groupInfo['websiteid']);
			if ($websiteId > 0)
			{
				$groupInfo['websiteid'] = null;
				$sql = "UPDATE `m_website_doc_website` SET `group` = $groupId WHERE `document_id` = $websiteId";
				$this->executeSQLQ($sql);
				
				$relationId = $this->getPersistentProvider()->getRelationId('group');
				$sql = "INSERT INTO `f_relation`(`relation_id1`, `relation_id2`, `relation_order`, `relation_name`, `document_model_id1`, `document_model_id2`, `relation_id`) 
					VALUES ($websiteId, $groupId, 0, 'group', 'modules_website/website', 'modules_users/group', $relationId)";
				$this->executeSQLQ($sql);
			}
			
			$countLinked = intval($groupInfo['linkedwebsites']);
			if ($countLinked > 0)
			{
				$groupInfo['linkedwebsites'] = 0;
				
				$groupRelationId = $this->getPersistentProvider()->getRelationId('group');
				
				$relationId = $this->getPersistentProvider()->getRelationId('linkedwebsites');
				$query = "SELECT relation_id2 FROM `f_relation` WHERE relation_id1 = $groupId AND relation_id = $relationId";
				foreach ($this->executeSQLQuery($query)->fetchAll(PDO::FETCH_COLUMN) as $websiteId) 
				{
					$sql = "UPDATE `m_website_doc_website` SET `group` = $groupId WHERE `document_id` = $websiteId";
					$this->executeSQLQ($sql);

					$sql = "INSERT INTO `f_relation`(`relation_id1`, `relation_id2`, `relation_order`, `relation_name`, `document_model_id1`, `document_model_id2`, `relation_id`) 
					VALUES ($websiteId, $groupId, 0, 'group', 'modules_website/website', 'modules_users/group', $groupRelationId)";
					$this->executeSQLQ($sql);
				}
				
			}
		}
		elseif ($groupInfo['document_model'] === 'modules_users/backendgroup' && (intval($groupInfo['isdefault']) != 1))
		{
			$groupInfo['document_model'] = 'modules_users/group';
		}
		elseif ($groupInfo['document_model'] === 'modules_users/backendgroup' && (intval($groupInfo['isdefault']) == 1))
		{
			$updateRelations = false;
		}
		else
		{
			$updateRelations = false;
			$this->logWarning('Unknow model ' . $groupInfo['document_model'] . ' for group ' . $groupId);
		}
		
		
		$sql = "UPDATE `m_users_doc_group` SET `document_modelversion` = '4.0', 
			document_model = '".$groupInfo['document_model']."', 
			websiteid = ". (is_null($groupInfo['websiteid']) ? 'NULL' : $groupInfo['websiteid']) .", 
			linkedwebsites = ". (is_null($groupInfo['linkedwebsites']) ? 'NULL' : $groupInfo['linkedwebsites']) ."
			WHERE `document_id` = $groupId";
		$this->executeSQLQ($sql);
		
		if (!$updateRelations)
		{
			return;
		}
		
		//Mise à jour des models
		$sql = "UPDATE `f_relation` SET `document_model_id1` = '".$groupInfo['document_model']."' WHERE `relation_id1` = $groupId";
		$this->executeSQLQ($sql);
		
		$sql = "UPDATE `f_relation` SET `document_model_id2` = '".$groupInfo['document_model']."' WHERE `relation_id2` = $groupId";
		$this->executeSQLQ($sql);
		
		$sql = "UPDATE `f_document` SET `document_model` = '".$groupInfo['document_model']."' WHERE `document_id` = $groupId";
		$this->executeSQLQ($sql);
	}
	
	function migrateUser($userId)
	{
		$query = "SELECT * FROM `m_users_doc_user` WHERE `document_id` = $userId";
		$rows = $this->executeSQLSelect($query)->fetchAll(PDO::FETCH_ASSOC);
		$userInfo = $rows[0];
		$userInfo['document_label'] = $userInfo['firstname'] .  ' ' . $userInfo['lastname'];
		
		if ($userInfo['document_model'] === 'modules_users/websitefrontenduser')
		{
			$userInfo['document_model'] = 'modules_users/user';
			$query = "SELECT `relation_id1` FROM `f_relation` WHERE `relation_id2` = $userId AND relation_name = 'sudoer'";
			$grpSudoer = $this->executeSQLSelect($query)->fetchAll(PDO::FETCH_COLUMN);
			if (is_array($grpSudoer) && count($grpSudoer))
			{
				$userInfo['issudoer'] = 1;
			}
		}
		elseif ($userInfo['document_model'] === 'modules_users/frontenduser')
		{
			$userInfo['document_model'] = 'modules_users/user';
		}
		elseif ($userInfo['document_model'] === 'modules_users/backenduser')
		{
			$userInfo['document_model'] = 'modules_users/user';
		}
		
		$this->migrateProfile($userInfo);
		
		$escapeLabel = $this->quote($userInfo['document_label']);
		$sql = "UPDATE `m_users_doc_user` SET `document_modelversion` = '4.0',  
			document_label = ". $escapeLabel.", 
			document_model = '".$userInfo['document_model']."', 
			issudoer = '".$userInfo['issudoer']."'
			WHERE `document_id` = $userId";
		$this->executeSQLQ($sql);
		
		//Mise à jour des models
		$sql = "UPDATE `f_relation` SET `document_model_id1` = '".$userInfo['document_model']."' WHERE `relation_id1` = $userId";
		$this->executeSQLQ($sql);
		
		$sql = "UPDATE `f_relation` SET `document_model_id2` = '".$userInfo['document_model']."' WHERE `relation_id2` = $userId";
		$this->executeSQLQ($sql);
		
		$sql = "UPDATE `f_document` SET `label_".$userInfo['document_lang']."` = ". $escapeLabel.", 
			`document_model` = '".$userInfo['document_model']."' WHERE `document_id` = $userId";
		$this->executeSQLQ($sql);
	}
	
	function migrateProfile($userInfo)
	{
		$userProfile = users_UsersprofileService::getInstance()->createQuery()->add(Restrictions::eq('accessorId', $userInfo['document_id']))->findUnique();
		if ($userProfile === null)
		{
			$userProfile = users_UsersprofileService::getInstance()->getNewDocumentInstance();
			$userProfile->setLabel($userInfo['document_label']);
			$userProfile->setAccessorId($userInfo['document_id']);
			$userProfile->setAccessorModel($userInfo['document_model']);
		}
		if (isset($userInfo['websiteid']) && intval($userInfo['websiteid']))
		{
			$userProfile->setRegisteredwebsiteid(intval($userInfo['websiteid']));
		}
		$userProfile->setTitleid($userInfo['titleid']);
		$userProfile->setFirstname($userInfo['firstname']);
		$userProfile->setLastname($userInfo['lastname']);
		
		$userProfile->save();
		
		$userPreferences = null;
		$noteContent = null;
		$dashboardcontent = null;
		
		if (!empty($userInfo['document_metas']))
		{
			$metas = unserialize($userInfo['document_metas']);
			if (is_array($metas) && isset($metas['userPreferences']))
			{
				$userPreferences = $metas['userPreferences'];
			}
			if (is_array($metas) && isset($metas['modules.dashboard.noteContent']))
			{
				$noteContent = $metas['modules.dashboard.noteContent'];
			}
			
		}
		if (!empty($userInfo['dashboardcontent']))
		{
			$dashboardcontent = $userInfo['dashboardcontent'];
		}
		
		if ($userPreferences !== null || $dashboardcontent !== null || $noteContent !== null)
		{
			$dashboardProfile = dashboard_DashboardprofileService::getInstance()->createQuery()->add(Restrictions::eq('accessorId', $userInfo['document_id']))->findUnique();
			if ($dashboardProfile === null)
			{
				$dashboardProfile = dashboard_DashboardprofileService::getInstance()->getNewDocumentInstance();
				$dashboardProfile->setLabel($userInfo['document_label']);
				$dashboardProfile->setAccessorId($userInfo['document_id']);
				$dashboardProfile->setAccessorModel($userInfo['document_model']);
			}
			$dashboardProfile->setDashboardcontent($dashboardcontent);
			$dashboardProfile->setUserPreferences($userPreferences);
			$dashboardProfile->setNoteContent($noteContent);
			$dashboardProfile->save();
		}
	}
	
	function quote($string)
	{
		return $this->getPersistentProvider()->getDriver()->quote($string);
	}
	
	function executeSQLQ($query)
	{
		$this->log($query);
		$this->executeSQLQuery($query);
	}
	
	/**
	 * @return array
	 */
	public function getPostCommandList()
	{
		return array(
			array('clear-documentscache'),
			array('enable-site'),
		);
	}
	
	/**
	 * @return string
	 */
	public function getExecutionOrderKey()
	{
		return '2011-10-10 12:04:11';
	}
		
	/**
	 * @return string
	 */
	public function getBasePath()
	{
		return dirname(__FILE__);
	}
	
    /**
     * @return false
     */
	public function isCodePatch()
	{
		return false;
	}
}