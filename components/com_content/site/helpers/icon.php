<?php
/**
 * @package		Joomla.Site
 * @subpackage	com_content
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * Content Component HTML Helper
 *
 * @static
 * @package		Joomla.Site
 * @subpackage	com_content
 * @since 1.5
 */
class JHtmlIcon
{
	static function create($category, $params)
	{
		$url = 'index.php?option=com_content&task=article.add&return='.base64_encode(urlencode(Request::current(true))).'&a_id=0&catid=' . $category->id;

		if ($params->get('show_icons'))
		{
			$text = JHtml::_('image', 'system/new.png', Lang::txt('JNEW'), NULL, true);
		}
		else
		{
			$text = Lang::txt('JNEW').'&#160;';
		}

		$button =  JHtml::_('link', Route::url($url), $text);

		$output = '<span class="hasTip" title="'.Lang::txt('COM_CONTENT_CREATE_ARTICLE').'">'.$button.'</span>';
		return $output;
	}

	static function email($article, $params, $attribs = array())
	{
		require_once PATH_CORE . '/components/com_mailto/helpers/mailto.php';

		$base     = JURI::getInstance()->toString(array('scheme', 'host', 'port'));
		$template = App::get('template')->template;
		$link     = $base . Route::url(ContentHelperRoute::getArticleRoute($article->slug, $article->catid, $article->language) , false);
		$url      = 'index.php?option=com_mailto&tmpl=component&template='.$template.'&link='.MailToHelper::addLink($link);

		$status = 'width=400,height=350,menubar=yes,resizable=yes';

		if ($params->get('show_icons'))
		{
			$text = JHtml::_('image', 'system/emailButton.png', Lang::txt('JGLOBAL_EMAIL'), NULL, true);
		}
		else
		{
			$text = '&#160;'.Lang::txt('JGLOBAL_EMAIL');
		}

		$attribs['title']   = Lang::txt('JGLOBAL_EMAIL');
		$attribs['onclick'] = "window.open(this.href,'win2','".$status."'); return false;";

		$output = JHtml::_('link', Route::url($url), $text, $attribs);
		return $output;
	}

	/**
	 * Display an edit icon for the article.
	 *
	 * This icon will not display in a popup window, nor if the article is trashed.
	 * Edit access checks must be performed in the calling code.
	 *
	 * @param	object	$article	The article in question.
	 * @param	object	$params		The article parameters
	 * @param	array	$attribs	Not used??
	 *
	 * @return	string	The HTML for the article edit icon.
	 * @since	1.6
	 */
	static function edit($article, $params, $attribs = array())
	{
		// Initialise variables.
		$userId = User::get('id');

		// Ignore if in a popup window.
		if ($params && $params->get('popup'))
		{
			return;
		}

		// Ignore if the state is negative (trashed).
		if ($article->state < 0)
		{
			return;
		}

		JHtml::_('behavior.tooltip');

		// Show checked_out icon if the article is checked out by a different user
		if (property_exists($article, 'checked_out') && property_exists($article, 'checked_out_time') && $article->checked_out > 0 && $article->checked_out != User::get('id'))
		{
			$checkoutUser = User::getInstance($article->checked_out);
			$button  = JHtml::_('image', 'system/checked_out.png', NULL, NULL, true);
			$date    = Date::of($article->checked_out_time)->toLocal();
			$tooltip = Lang::txt('JLIB_HTML_CHECKED_OUT').' :: '.Lang::txt('COM_CONTENT_CHECKED_OUT_BY', $checkoutUser->name).' <br /> '.$date;
			return '<span class="hasTip" title="'.htmlspecialchars($tooltip, ENT_COMPAT, 'UTF-8').'">'.$button.'</span>';
		}

		$url  = 'index.php?option=com_content&task=article.edit&a_id='.$article->id.'&return='.base64_encode(urlencode(Request::current(true)));
		$icon = $article->state ? 'edit.png' : 'edit_unpublished.png';
		if (strtotime($article->publish_up) > strtotime(Date::of('now')))
		{
			$icon = 'edit_unpublished.png';
		}
		$text = JHtml::_('image', 'system/'.$icon, Lang::txt('JGLOBAL_EDIT'), NULL, true);

		if ($article->state == 0)
		{
			$overlib = Lang::txt('JUNPUBLISHED');
		}
		else {
			$overlib = Lang::txt('JPUBLISHED');
		}

		$date = Date::of($article->created)->toLocal();
		$author = $article->created_by_alias ? $article->created_by_alias : $article->author;

		$overlib .= '&lt;br /&gt;';
		$overlib .= $date;
		$overlib .= '&lt;br /&gt;';
		$overlib .= Lang::txt('COM_CONTENT_WRITTEN_BY', htmlspecialchars($author, ENT_COMPAT, 'UTF-8'));

		$button = JHtml::_('link', Route::url($url), $text);

		$output = '<span class="hasTip" title="'.Lang::txt('COM_CONTENT_EDIT_ITEM').' :: '.$overlib.'">'.$button.'</span>';

		return $output;
	}


	static function print_popup($article, $params, $attribs = array())
	{
		$url  = ContentHelperRoute::getArticleRoute($article->slug, $article->catid, $article->language);
		$url .= '&tmpl=component&print=1&layout=default&page='. @ $request->limitstart;

		$status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

		// checks template image directory for image, if non found default are loaded
		if ($params->get('show_icons'))
		{
			$text = JHtml::_('image', 'system/printButton.png', Lang::txt('JGLOBAL_PRINT'), NULL, true);
		}
		else
		{
			$text = Lang::txt('JGLOBAL_ICON_SEP') .'&#160;'. Lang::txt('JGLOBAL_PRINT') .'&#160;'. Lang::txt('JGLOBAL_ICON_SEP');
		}

		$attribs['title']   = Lang::txt('JGLOBAL_PRINT');
		$attribs['onclick'] = "window.open(this.href,'win2','".$status."'); return false;";
		$attribs['rel']     = 'nofollow';

		return JHtml::_('link', Route::url($url), $text, $attribs);
	}

	static function print_screen($article, $params, $attribs = array())
	{
		// checks template image directory for image, if non found default are loaded
		if ($params->get('show_icons'))
		{
			$text = JHtml::_('image', 'system/printButton.png', Lang::txt('JGLOBAL_PRINT'), NULL, true);
		}
		else
		{
			$text = Lang::txt('JGLOBAL_ICON_SEP') .'&#160;'. Lang::txt('JGLOBAL_PRINT') .'&#160;'. Lang::txt('JGLOBAL_ICON_SEP');
		}
		return '<a href="#" onclick="window.print();return false;">'.$text.'</a>';
	}
}
