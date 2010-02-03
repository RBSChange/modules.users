<?php
/**
 * users_persistentdocument_preferences
 * @package users
 */
class users_persistentdocument_preferences extends users_persistentdocument_preferencesbase
{
	/**
	 * @see f_persistentdocument_PersistentDocumentImpl::getLabel()
	 *
	 * @return String
	 */
	public function getLabel()
	{
		return f_Locale::translateUI(parent::getLabel());
	}		
}