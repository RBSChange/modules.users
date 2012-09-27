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
			$templateName = 'Users-Login-' . $lang;
			$path = change_TemplateLoader::getNewInstance()->setExtension('html')->getPath('modules', 'users', 'templates', $templateName);
			if ($path === null)
			{
				$templateName = 'Users-Login-en';
			}
			$this->setTemplateName($templateName, 'html');
			if (!$request->hasParameter('access'))
			{
				Framework::warn(__METHOD__ . " No informations to display an authentication in front office");
			}
			$this->setAttribute('UIHOST', Framework::getUIBaseUrl());
			$link = new f_web_ChromeParametrizedLink('rbschange/content/ext/' . PROJECT_ID);
			$link->setQueryParameters(array('module' => 'uixul', 'action' => 'Admin'));
			$link->setArgSeparator(f_web_HttpLink::STANDARD_SEPARATOR);
			$this->setAttribute('xchromeURL', $link->getUrl());
			$rq->endI18nWork();
		}
		catch (Exception $e)
		{
			$rq->endI18nWork($e);
		}
	}
}
