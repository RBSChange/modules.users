<?php
class users_BlockChangepasswordAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::INPUT;
		}
		
		$currentUser = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($currentUser === null)
		{
			return website_BlockView::NONE;
		}
		$request->setAttribute('currentUser', $currentUser);

		$ls = LocaleService::getInstance();
		if ($request->hasParameter('submit'))
		{
			$oldpassword = trim($request->getParameter('oldpassword'));
			$password = trim($request->getParameter('password'));
			$confirmpassword = trim($request->getParameter('confirmpassword'));
			if (!empty($oldpassword) && !empty($password)  && !empty($confirmpassword))
			{
				if ($password == $confirmpassword)
				{
					$us = users_UserService::getInstance();
					if ($us->checkIdentity($currentUser, $oldpassword))
					{
						$tm = f_persistentdocument_TransactionManager::getInstance();

						try
						{
							$tm->beginTransaction();
							$currentUser->setPassword($password);
							$currentUser->save();
							$tm->commit();
							return  website_BlockView::SUCCESS;
						}
						catch (Exception $e)
						{
							Framework::exception($e);
							$tm->rollBack($e);
							$error = $ls->transFO('m.users.frontoffice.changepassword.exception');
							$this->addError($error);
							// For compatibility. Will be removed in 4.0.
							$request->setAttribute('errors', array($error));
						}
					}
					else
					{
						$error = $ls->transFO('m.users.frontoffice.changepassword.invalidoldpassword');
						$this->addError($error);
						// For compatibility. Will be removed in 4.0.
						$request->setAttribute('errors', array($error));
					}
				}
				else
				{
					$error = $ls->transFO('m.users.frontoffice.changepassword.notconfirmpassword');
					$this->addError($error);
					// For compatibility. Will be removed in 4.0.
					$request->setAttribute('errors', array($error));
				}
			}
			else
			{
				$error = $ls->transFO('m.users.frontoffice.changepassword.emptypassword');
				$this->addError($error);
				// For compatibility. Will be removed in 4.0.
				$request->setAttribute('errors', array($error));
			}
		}
		return website_BlockView::INPUT;
	}
}