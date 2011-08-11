<?php
$group = DocumentHelper::getDocumentInstance($arguments[0], 'modules_users/dynamicfrontendgroup');
$userIdArray = array_slice($arguments, 1);
foreach ($userIdArray as $userId) 
{
	try 
	{
		Framework::fatal(__METHOD__ . ' ' . $userId);
		$user = DocumentHelper::getDocumentInstance($userId, 'modules_users/frontenduser');
		$user->addGroups($group);
		$user->save();
	} 
	catch (Exception $e)
	{
		Framework::exception($e);
		die($e->getMessage());
	}
}
echo 'OK';