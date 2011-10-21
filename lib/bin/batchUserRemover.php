<?php
$group = users_persistentdocument_dynamicgroup::getInstanceById($arguments[0]);
$userIdArray = array_slice($arguments, 1);
foreach ($userIdArray as $userId) 
{
	try 
	{
		$user = users_persistentdocument_user::getInstanceById($userId);
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