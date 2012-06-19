<?php
/**
 * @package modules.users.libs.validation
 */
class validation_LoginValidator extends validation_ValidatorImpl implements validation_Validator
{
	/**
	 * @param validation_Property $Field
	 * @param validation_Errors $errors
	 * @return void
	 */
	protected function doValidate(validation_Property $field, validation_Errors $errors)
	{
		$param = $this->getParameter();
		$user = null;
		$groupIds = array();
		if (is_numeric($param))
		{
			try 
			{
				$user = users_persistentdocument_user::getInstanceById($param);
				$groupIds = DocumentHelper::getIdArrayFromDocumentArray($user->getGroupsArray());
			}
			catch (Exception $e)
			{
				Framework::exception($e);
				$securityLevel = self::SECURITY_LEVEL_MINIMAL;
			}
		}
		$newLogin = $field->getValue();
		
		if (!users_UserService::getInstance()->validateUserLogin($newLogin, $user, $groupIds))
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
		return '&modules.users.bo.validation.validator.login.Message;';
	}
}