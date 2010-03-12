<?php
/**
 * users_BlockEditprofilAction
 * @package modules.users.lib.blocks
 */
class users_BlockEditprofilAction extends website_BlockAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($user === NULL)
		{
			return block_BlockView::NONE;
		}
		
		$request->setAttribute('user', $user);
		
		$module = $request->getParameter('blockModule', 'users');
		$blockName = $request->getParameter('blockName', 'EditFrontendUserProfile');
		
		$list = list_ListService::getInstance()->getByListId('modules_users/editprofilepanels');
		$panels = array();
		foreach ($list->getItems() as $item)
		{
			list ($panelModule, $panelBlockName) = explode('/', $item->getValue());
			$isCurrent = ($panelModule == $module && $blockName == $panelBlockName);
			$panel = array(
				'label' => $item->getLabel(),
				'module' => $panelModule,
				'blockName' => $panelBlockName,
				'isCurrent' => $isCurrent
			);
			if ($isCurrent)
			{
				$request->setAttribute('currentPanel', $panel);
			}
			$panels[] = $panel;
		}
		$request->setAttribute('panels', $panels);
		
		return website_BlockView::SUCCESS;
	}
}