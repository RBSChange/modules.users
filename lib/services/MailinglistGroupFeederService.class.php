<?php
/**
 * users_MailinglistGroupFeederService
 * @package modules.emailing
 */
class users_MailinglistGroupFeederService extends emailing_MailinglistFeederBaseService
{
	/**
	 * @var users_MailinglistGroupFeederService
	 */
	private static $instance;

	/**
	 * @return users_MailinglistGroupFeederService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}	

	/**
	 * @param emailing_persistentdocument_dynamicmailinglist $list
	 */
	public function getRelatedIds($list)
	{
		$userIds = array();
		$groupId = $list->getParameter('groupId');
		if ($groupId !== null)
		{
			$query = users_UserService::getInstance()->createQuery();
			$query->add(Restrictions::eq('groups.id', $groupId));
			$query->add(Restrictions::published());
			$query->setProjection(Projections::property('id'));
			foreach ($query->find() as $row)
			{
				$userIds[] = $row['id'];
			}
		}
		return $userIds;
	}
	
	/**
	 * @param Integer $id
	 * @param emailing_persistentdocument_dynamicmailinglist $list
	 */
	public function refreshSubscriber($id, $list)
	{
		$fields = $this->getFields($list);
		$user = DocumentHelper::getDocumentInstance($id);
		$subscriber = $this->getSubscriber($user->getId(), $user->getEmail(), $list);				
		$subscriber->setEmail($user->getEmail());
		$subscriber->setRelatedDocument($user);	
		$subscriber->setExtendFieldValue($fields['firstname'], $user->getFirstname());
		$subscriber->setExtendFieldValue($fields['lastname'], $user->getLastname());
		if ($user->getTitle() !== null)
		{
			$subscriber->setExtendFieldValue($fields['title'], $user->getTitle()->getLabel());
		}
		$subscriber->setDisable(false);
		$subscriber->save();
		$subscriber->activate();
	}
}