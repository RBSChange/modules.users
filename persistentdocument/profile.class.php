<?php
/**
 * Class where to put your custom methods for document users_persistentdocument_profile
 * @package modules.users.persistentdocument
 */
class users_persistentdocument_profile extends users_persistentdocument_profilebase
{
	/**
	 * @param f_persistentdocument_PersistentDocument $accessor
	 */
	public function setAccessor($accessor)
	{
		$this->setAccessorId($accessor->getId());
		$this->setAccessorModel($accessor->getDocumentModelName());
		$this->setLabel($accessor->getLabel());
	}
}