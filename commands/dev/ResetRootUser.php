<?php
class commands_ResetRootUser extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "[--login=wwwadmin]";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "reset email and password of root account";
	}
	
	function getOptions()
	{
		return array('login');
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return true;
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Reset Root User ==");
		if (isset($options['login']) && is_string($options['login']) && !empty($options['login']))
		{
			$login = $options['login'];
		}
		else
		{
			$login = "wwwadmin";
		}
		$reseted = false;
		$this->loadFramework();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		$pp = $tm->getPersistentProvider();
		try 
		{
			$tm->beginTransaction();
			$backEndGroupID = users_BackendgroupService::getInstance()->getBackendGroupId(); 
			$users = users_UserService::getInstance()->getRootUsersByGroupId($backEndGroupID);
			foreach ($users as $user) 
			{
				/* @var $user users_persistentdocument_user */
				if ($user->getLogin() === $login)
				{
					$user->setPasswordmd5(null);
					$user->setEmail(null);
					$pp->updateDocument($user);
					$reseted = true;
					break;
				}
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
		if ($reseted)
		{
			$this->quitOk("Root account reseted");
		}
		else
		{
			$this->quitError("Root account '$login' not found");
		}
	}
}