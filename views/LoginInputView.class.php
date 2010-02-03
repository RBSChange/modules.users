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
        	$rq->beginI18nWork($rq->getUILang());
        	
        	$this->forceModuleName("users");
			$this->setTemplateName('Users-Login', K::HTML);
	
			if (!$request->hasParameter('access'))
			{
				Framework::log("No informations to display an authentication in front office", Logger::WARN);
			}
			    	
	        $this->setAttribute('cssInclusion',
	           $this->getStyleService()
		    	  ->registerStyle('modules.generic.frontoffice')
		    	  ->registerStyle('modules.users.backoffice')
		    	  ->execute(K::HTML)
		    );
	        
	        $this->setAttribute('UIHOST',  Framework::getUIBaseUrl());
			$rq->endI18nWork();
		}
		catch (Exception $e)
		{
			$rq->endI18nWork($e);
		}
	 }
}
