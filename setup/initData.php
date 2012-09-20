<?php
class users_Setup extends object_InitDataSetup
{
	public function install()
	{
		$scriptReader = import_ScriptReader::getInstance();
		$this->executeModuleScript('init.xml');
		
		$this->getTransactionManager()->beginTransaction();

		$wwwadmins = users_UserService::getInstance()->getUsersByLoginAndGroup('wwwadmin', users_BackendgroupService::getInstance()->getBackendGroup());
		$wwwadmin = $wwwadmins[0];
		$wwwadmin->setPasswordmd5(null);
		$wwwadmin->setEmail(null);
		$this->getPersistentProvider()->updateDocument($wwwadmin);
		
		$this->getTransactionManager()->commit();
	}
}