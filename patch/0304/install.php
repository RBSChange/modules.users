<?php
/**
 * users_patch_0304
 * @package modules.users
 */
class users_patch_0304 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
//	public function isCodePatch()
//	{
//		return true;
//	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		try 
		{
			$this->execChangeCommand('compile-documents');
			$this->executeSQLQuery("DELETE FROM f_relation where relation_id = (select relation_id from f_relationname where property_name='title') AND relation_id1 IN (select document_id from m_users_doc_user)");
			$this->executeSQLQuery('ALTER TABLE m_users_doc_user CHANGE COLUMN title titleid INT(11)');
		}
		catch (Exception $e)
		{
			$this->logError($e->getMessage());
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
		return '0304';
	}
}