<?php
/**
 * @author inthause & intportg
 * @package modules.users.libs.validation
 * 
 * This validator may only be used to validate the login field of a user. 
 * It may not be used to validate an other field.
 * 
 * The validation rules for the login are the following :
 * 
 * We can have:
 * - a frontend user with the same login than a backend one.
 * - a website frontend user with the same login than a backend one.
 * - a website frontend user with the same login than an other website frontend user attached to an other website.
 *
 * We can't have:
 * - two backend users whith the same login.
 * - two frontend users with the same login.
 * - a frontend user with the same login than a website frontend one.
 * - a website frontend user with the same login than a website frontend user attached to the same website.
 */
class validation_LoginValidator extends validation_UniqueValidator
{
	/**
	 * @param validation_Property $Field
	 * @param validation_Errors $errors
	 * @return void
	 */
	protected function doValidate(validation_Property $field, validation_Errors $errors)
	{
		if ($this->getParameter() != true)
		{
			return;
		}
		
		$model = $this->getDocumentModel();
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' -> ' . $model->getName());
		}
		
		$us = users_UserService::getInstance();
		$login = $field->getValue();
		$user = null;
		if ($model->isModelCompatible('modules_users/websitefrontenduser'))
		{
			$user = $us->getFrontendUserByLogin($login, $this->getWebsiteId());
		}
		else if ($model->isModelCompatible('modules_users/frontenduser'))
		{
			$user = $us->getFrontendUserByLogin($login);
			if ($user === null)
			{
				$user = f_util_ArrayUtils::firstElement(users_WebsitefrontenduserService::getInstance()->createQuery()->add(Restrictions::eq('login', $login))->find());
			}
		}
		else if ($model->isModelCompatible('modules_users/backenduser'))
		{
			$user = $us->getBackEndUserByLogin($login);
		}
		else
		{
			throw new Exception('Invalid model (must be a backenduser or a frontenduser)');
		}
		
		if ($user !== null && !DocumentHelper::equals($user, $this->document))
		{
			$this->reject($field->getName(), $errors);
		}
	}
	
	/**
	 * Returns the error message.
	 * @return string
	 */
	protected function getMessage()
	{
		$key = '&modules.users.bo.validation.validator.login.Message;';
		return f_Locale::translate($key, array('param' => $this->getParameter()));
	}
	
	/**
	 * @return Integer
	 */
	private function getWebsiteId()
	{
		if ($this->getDocumentId() !== null)
		{
			return $this->document->getWebsiteid();
		}
		
		if ($this->parentId === null && $this->document !== null)
		{
			$this->parentId = $this->document->getParentNodeId();
		}
		
		$group = DocumentHelper::getDocumentInstance($this->parentId);
		if ($group instanceof users_persistentdocument_websitefrontendgroup)
		{
			return $group->getWebsiteid();
		}
		else
		{
			throw new Exception('Invalid parent (must be a websitefrontendgroup)');
		}
	}
	
	/**
	 * @return Integer
	 */
	private function getDocumentId()
	{
		if ($this->document !== null && !$this->document->isNew())
		{
			return $this->document->getId();
		}
		return null;
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	private function getDocumentModel()
	{
		if (empty($this->documentModelName) && $this->document instanceof f_persistentdocument_PersistentDocument)
		{
			$this->documentModelName = $this->document->getDocumentModelName();
		}
		if (empty($this->documentModelName))
		{
			throw new ValidatorConfigurationException('validation_LoginValidator requires a valid Document Model name.');
		}
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->documentModelName);
	}
}