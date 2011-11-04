<?php
/**
 * users_BlockUserAction
 * @package modules.users.lib.blocks
 */
class users_BlockUserAction extends website_BlockAction
{
	/**
	 * @return array<String, String>
	 */
	public function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof users_persistentdocument_user)
		{
			$date = date_Formatter::toDefaultDate($doc->getCreationdate());
			return array('label' => $doc->getLabel(), 'registrationdate' => $date);
		}
		return array();
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$user = $this->getDocumentParameter();
		if ($user === null)
		{
			return website_BlockView::NONE;
		}		
		$request->setAttribute('user', $user);
		$userId = $user->getId();
		
		$ls = LocaleService::getInstance();
		$ps = users_ProfileService::getInstance();
		$profiles = array();
		$resolver = TemplateResolver::getInstance()->setDirectory('templates')->setMimeContentType('html');
		foreach ($this->getProfileNames() as $profileName)
		{
			$template = ucfirst($profileName) . '-Inc-Profile-View';
			if ($resolver->setPackageName('modules_'.$profileName)->getPath($template))
			{
				$profiles[$profileName] = array(
					'label' => $ls->trans('m.'.$profileName.'.document.'.$profileName.'profile.document-name'),
					'instance' => $ps->getByAccessorIdAndName($userId, $profileName),
					'module' => $profileName,
					'template' => $template
				);
			}
		}
		$request->setAttribute('profiles', $profiles);
		
		$currentUser = users_UserService::getInstance()->getCurrentUser();
		$request->setAttribute('currentUser', $currentUser);
		$request->setAttribute('itsMyProfile', DocumentHelper::equals($currentUser, $user));
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @return string[]
	 */
	public function getProfileNames()
	{
		$order = explode(',', Framework::getConfigurationValue('modules/users/profileNamesOrder', 'users'));
		foreach (users_ProfileService::getInstance()->getProfileNames() as $name)
		{
			if (!in_array($name, $order))
			{
				$order[] = $name;
			}
		}
		return $order;
	}
}