<?php
/**
 * users_BlockSuAction
 * @package modules.users.lib.blocks
 */
class users_BlockSuAction extends website_TaggerBlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackofficeEdition())
		{
			return website_BlockView::NONE;
		}
		$this->setUserList($request);
		return website_BlockView::SUCCESS;
	}

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeFilter($request, $response)
	{
		$this->setUserList($request);
		return website_BlockView::SUCCESS;
	}

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeSwitch($request, $response)
	{
		$ls = LocaleService::getInstance();
		if ($request->hasParameter('userId'))
		{
			$user = DocumentHelper::getDocumentInstance($request->getParameter('userId'));
			$request->setAttribute('user', $user);
			if (users_WebsitefrontenduserService::getInstance()->su($user))
			{
				return 'SwitchSuccess';
			}
			else
			{
				$this->addError($ls->transFO('m.users.frontoffice.su.switched-error-do-not-have-permission', array('ucf'), array('fullName' => $user->getFullName(), 'login' => $user->getLogin())));
			}
		}
		else
		{
			$this->addError($ls->transFO('m.users.frontoffice.su.switched-error-bad-arguments', array('ucf')));
		}
		return 'SwitchError';
	}

	/**
	 * Called when the block is inserted into a page content:
	 * hide page From Menus And SiteMap and call website_TaggerBlockAction::onPageInsertion()
	 * @param website_persistentdocument_Page $page
	 * @param Boolean $absolute true if block was introduced considering all versions (langs) of the page
	 * @see lib/blocks/website_TaggerBlockAction#onPageInsertion($page, $absolute)
	 */
	function onPageInsertion($page, $absolute = false)
	{
		if ($page->getNavigationVisibility() != 0)
		{
			$page->setNavigationvisibility(0);
			$page->save();
		}
		parent::onPageInsertion($page, $absolute);
	}

	// Private methods.

	/**
	 * @param f_mvc_Request $request
	 */
	private function setUserList($request)
	{
		$words = $request->getParameter('userWords', '');
		$pageIndex = $request->getParameter('page', 1);

		$itemsPerPage = $this->findParameterValue('itemsperpage');
		if ($itemsPerPage < 1)
		{
			$itemsPerPage = 10;
		}

		if ($this->getConfiguration()->getMasknonfiltered() && $words == '')
		{
			$documents = array();
			$totalHitsCount = 0;
		}
		else
		{
			$currentUser = users_WebsitefrontenduserService::getInstance()->getCurrentFrontEndUser();
			$totalHitsCount = users_WebsitefrontenduserService::getInstance()->getSuableCountByUser($currentUser, $words);
			$documents = users_WebsitefrontenduserService::getInstance()->getSuableByUser($currentUser, $words, $itemsPerPage * ($pageIndex - 1), $itemsPerPage);
		}

		$paginator = new paginator_Paginator('users', $pageIndex, $documents, $itemsPerPage);
		$paginator->setPageCount((int)ceil($totalHitsCount / $itemsPerPage));

		$request->setAttribute('usersCount', $totalHitsCount);
		$request->setAttribute('users', $paginator);
	}
}