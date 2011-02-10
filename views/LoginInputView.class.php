<?php
class users_LoginInputView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
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
        	$path = TemplateResolver::getInstance()->setPackageName('modules_users')->setDirectory('templates')->getPath($templateName);        	
        	if ($path === null)
        	{
        		$templateName = 'Users-Login-en';
        	}
			$this->setTemplateName($templateName, K::HTML);
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
