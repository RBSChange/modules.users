<?php
$userIds = $_POST['argv'][0];

$tm = f_persistentdocument_TransactionManager::getInstance();
$pp = f_persistentdocument_PersistentProvider::getInstance();

try
{
	$tm->beginTransaction();	
	$query = users_UserService::getInstance()->createQuery()
		->add(Restrictions::in("id", $userIds));
	$users = $query->find();
	foreach ($users as $user)
	{
		$user->setLogin(f_util_StringUtils::strtolower($user->getLogin()));
		$pp->updateDocument($user);
	}
	$tm->commit();
	echo count($users);
}
catch (Exception $e)
{
	$tm->rollBack($e);
	echo "ERROR: ".$e->getMessage();
}