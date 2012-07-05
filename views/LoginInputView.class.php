<?php
class users_LoginInputView extends change_View
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$rq = RequestContext::getInstance();
		try 
		{
			$lang = $rq->getUILang();
			$rq->beginI18nWork($lang);
			$this->forceModuleName("users");
			$templateName = 'Users-Login-'.$lang;
			$path = change_TemplateLoader::getNewInstance()->setExtension('html')
				->getPath('modules', 'users', 'templates', $templateName) ;
			if ($path === null)
			{
				$templateName = 'Users-Login-en';
			}
			$this->setTemplateName($templateName, 'html');
			if (!$request->hasParameter('access'))
			{
				Framework::warn(__METHOD__ . " No informations to display an authentication in front office");
			}
			$this->setAttribute('UIHOST',  Framework::getUIBaseUrl());
			$rq->endI18nWork();
		}
		catch (Exception $e)
		{
			$rq->endI18nWork($e);
		}
	 }
}
