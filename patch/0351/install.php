<?php
/**
 * users_patch_0351
 * @package modules.users
 */
class users_patch_0351 extends patch_BasePatch
{

 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$caseSensitive = users_UserService::getInstance()->isLoginCaseSensitive();
		if ($caseSensitive)
		{
			$this->log("Login is case sensitive => do nothing");
			return;
		}
		
		$this->log("Login is not case sensitive => trying to migrate data");
		
		foreach (array('frontenduser', 'backenduser') as $docName)
		{
			$model = f_persistentdocument_PersistentDocumentModel::getInstance('users', $docName);
			$modelNames = array($model->getName());
			if (is_array($model->getChildrenNames()))
			{
				$modelNames = array_merge($modelNames, $model->getChildrenNames());
			}
			$sqlIn = "document_model IN ('" . implode("', '", $modelNames) . "')";
			$stmt = $this->executeSQLSelect("SELECT count(*) as `logincount`, lower(`login`) as `llogin` FROM `m_users_doc_user` WHERE $sqlIn group by `llogin` having `logincount` > 1");
			
			$rows = $stmt->fetchAll();
			if (count($rows) > 0)
			{
				$this->logError("Can not migrate $docName user logins: there are ".count($rows)." duplicates. See below for details: ");
				foreach ($rows as $row)
				{
					$this->log($row["llogin"].", used ".$row["logincount"]." times");
				}
				throw new Exception("\nYou should:\n- activate login case sensitivity turning to true modules/users/loginCaseSensitive, or\n- Fix your database");
			}
		}

		$scriptPath = "modules/users/patch/0351/migrateUsersLogins.php";
		$ids = users_UserService::getInstance()->createQuery()
			->setProjection(Projections::property("id", "i"))
			->addOrder(Order::asc("id"))
			->findColumn("i");
			
		$idsCount = count($ids);
		$offset = 0;
		$chunkLength = 100;
		while ($offset < $idsCount)
		{
			$subIds = array_slice($ids, $offset, $chunkLength);
			$ret = f_util_System::execHTTPScript($scriptPath, array($subIds));
			if (!is_numeric($ret))
			{
				$this->logError("Error while processing ".($offset * $chunkLength)." - ".(($offset+1)*$chunkLength).": $ret");
			}
			else
			{
				$this->log(($offset * $chunkLength)." - ".(($offset+1)*$chunkLength)." processed: ".$ret." documents updated");
			}
			$offset += $chunkLength;
		}
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'users';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0351';
	}
}