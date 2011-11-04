<?php
class change_LoginConstraint extends Zend_Validate_Abstract
{
    const INVALID_LOGIN = 'invalidLogin';
    
     /**
     * @var integer
     */
    protected $_userId = 0;
    
 	/**
	 * @param array $params <documentId => integer || [parameter => integer,]>
	 */   
    public function __construct($params = array())
    {
		$this->_messageTemplates = array(self::INVALID_LOGIN => LocaleService::getInstance()->trans('f.constraints.notunique', array('ucf')));
    	if (isset($params['documentId']) && intval($params['documentId']) > 0)
    	{
    		$this->_userId = intval($params['documentId']);
    	}
    	elseif (isset($params['parameter']) && intval($params['parameter']) > 0)
    	{
    		$this->_userId = intval($params['parameter']);
    	}
    }
    
    /**
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value)
    {
    	$this->_setValue($value);
    	$user = null;
		$groupIds = array();
		if ($this->_userId > 0)
		{
			try 
			{
				$user = users_persistentdocument_user::getInstanceById($this->_userId);
				$groupIds = DocumentHelper::getIdArrayFromDocumentArray($user->getGroupsArray());
			}
			catch (Exception $e)
			{
				Framework::exception($e);
			}
		}		
		if (!users_UserService::getInstance()->validateUserLogin($value, $user, $groupIds))
		{
			$this->_error(self::INVALID_LOGIN);
            return false;
		}
        return true;
    }    
}