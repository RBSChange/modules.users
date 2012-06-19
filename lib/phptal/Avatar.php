<?php
// change:avatar
//
// This extension requires at least one of the following parameter:
//   - user: modules_users/user
//   - userId: integer
//   - email: string
// Some optionnal configuration parameters are available:
//   - size: integer (default 32)
//	 - defaultImage: modules_media/media (default null)
//   - rating: string (specific to gravatar.com, default 'g')
// Examples:
//	 <img change:avatar="user author" />
//   <img class="avatar document-visual" change:avatar="email comment/getEmail; user comment/getAuthoridInstance" size="48" />

/**
 * @package forums.list.phptal
 */
class PHPTAL_Php_Attribute_CHANGE_Avatar extends ChangeTalAttribute 
{	
	/**
	 * @return boolean
	 */
	protected function evaluateAll()
	{
		return true;
	}
	
	/**
	 * @param Array $params
	 * @return string
	 */
	public static function renderAvatar($params)
	{
		$html = array();
		
		$class = self::getFromParams('class', $params, 'image');
		$alt = self::getFromParams('alt', $params);
		$html[] = '<img class="' . $class . '" alt="' . $alt . '"';
		
		$title = self::getFromParams('title', $params);
		if ($title)
		{
			$html[] = 'title="' . $title . '"';
		}
		
		$size = self::getFromParams('size', $params, '32');
		$rating = self::getFromParams('rating', $params, 'g');
		$user = self::getFromParams('user', $params);
		if ($user === null)
		{
			$userId = intval(self::getFromParams('userId', $params));
			if ($userId > 0)
			{
				$model = f_persistentdocument_PersistentProvider::getInstance()->getDocumentModelName($userId);
				if ($model)
				{
					$user = DocumentHelper::getDocumentInstance($userId, $model);
				}
			}
		}
		if ($user instanceof users_persistentdocument_user)
		{
			$email = $user->getEmail();
		}
		else 
		{
			$email = self::getFromParams('email', $params);
		}
				
		$src = 'http://www.gravatar.com/avatar/' . md5($email) . '?s=' . $size . '&amp;r=' . $rating;
		$defaultImage = self::getFromParams('defaultImage', $params);
		if ($defaultImage instanceof media_persistentdocument_media)
		{
			$src .= '&amp;d=' . urlencode(LinkHelper::getDocumentUrl($defaultImage, null, array('max-width' => $size, 'max-height' => $size)));
		}
		$html[] = 'src="' . $src . '"';
				
		$html[] = '/>';
		return implode(' ', $html);
	}
	
	/**
	 * @param string $key
	 * @param array $params
	 * @return string
	 */
	public static function getFromParams($key, $params, $default = null)
	{
		return (array_key_exists($key, $params)) ? $params[$key] : $default;
	}
}