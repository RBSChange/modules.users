<?php
$group = DocumentHelper::getDocumentInstance($_POST['argv'][0], 'modules_users/dynamicfrontendgroup');
$userIdArray = array_slice($_POST['argv'], 1);
foreach ($userIdArray as $userId) 
{
	try 
	{
		$user = DocumentHelper::getDocumentInstance($userId, 'modules_users/frontenduser');
		$user->removeGroups($group);
		$user->save();
	} 
	catch (Exception $e)
	{
		Framework::exception($e);
		die($e->getMessage());
	}
}
echo 'OK';
