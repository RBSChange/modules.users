<?php
/**
 * users_BlockUsersListAction
 * @package modules.users.lib.blocks
 */
class users_BlockUsersListAction extends website_BlockAction
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

		$us = users_UserService::getInstance();
		$website = website_WebsiteService::getInstance()->getCurrentWebsite();
		$count = $us->getPublishedCountByWebsite($website);
		$nbItemPerPage = $this->getConfiguration()->getItemsperpage();
		$page = $request->getParameter('page');
		if (!is_numeric($page) || $page < 1 || $page > ceil($count / $nbItemPerPage))
		{
			$page = 1;
		}
		$this->getContext()->addCanonicalParam('page', $page > 1 ? $page : null, $this->getModuleName());
		$users = $us->getPublishedByWebsite($website, ($nbItemPerPage * ($page - 1)) + 1, $nbItemPerPage);
		$paginator = new paginator_Paginator($this->getModuleName(), $page, $users, $nbItemPerPage);
		$paginator->setItemCount($count);
		$request->setAttribute('paginator', $paginator);

		return website_BlockView::SUCCESS;
	}
}