<?php
class commands_ResetRootUser extends commands_AbstractChangedevCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "reset email and password of root account";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return true;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Reset Root User ==");
		$this->loadFramework();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		$pp = $tm->getPersistentProvider();
       	try 
       	{
       		$tm->beginTransaction();
       		$wwwadmin = users_UserService::getInstance()->getBackEndUserByLogin('wwwadmin');
       		if ($wwwadmin instanceof users_persistentdocument_backenduser) 
       		{
       			$wwwadmin->setPasswordmd5(null);
       	 		$wwwadmin->setEmail(null);
       		 	$pp->updateDocument($wwwadmin);
       		}
       		else
       		{
       			throw new Exception('Invalid root account');			
       		}
       		$tm->commit();
       		
       	}
       	catch (Exception $e)
       	{
       		$tm->rollBack($e);
       		throw $e;
       	}
		$this->quitOk("Root account reseted");
	}
}