<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2013 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2013 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Answers model for a comment
 */
class plgHubzeroCommentsModelComment extends \Hubzero\Base\Model
{
	/**
	 * Table class name
	 *
	 * @var string
	 */
	protected $_tbl_name = '\\Hubzero\\Item\\Comment';

	/**
	 * Model context
	 *
	 * @var string
	 */
	protected $_context = 'plg_hubzero_comments.comment.content';

	/**
	 * \Hubzero\User\Profile
	 *
	 * @var object
	 */
	protected $_creator = NULL;

	/**
	 * \Hubzero\Base\ItemList
	 *
	 * @var object
	 */
	private $_comments = null;

	/**
	 * Comment count
	 *
	 * @var integer
	 */
	private $_comments_count = null;

	/**
	 * URL for this entry
	 *
	 * @var string
	 */
	private $_base = null;

	/**
	 * Return a formatted timestamp
	 *
	 * @param      string $as What format to return
	 * @return     string
	 */
	public function created($as='')
	{
		switch (strtolower($as))
		{
			case 'date':
				return JHTML::_('date', $this->get('created'), JText::_('DATE_FORMAT_HZ1'));
			break;

			case 'time':
				return JHTML::_('date', $this->get('created'), JText::_('TIME_FORMAT_HZ1'));
			break;

			default:
				return $this->get('created');
			break;
		}
	}

	/**
	 * Get the creator of this entry
	 *
	 * Accepts an optional property name. If provided
	 * it will return that property value. Otherwise,
	 * it returns the entire JUser object
	 *
	 * @return     mixed
	 */
	public function creator($property=null)
	{
		if (!($this->_creator instanceof \Hubzero\User\Profile))
		{
			$this->_creator = \Hubzero\User\Profile::getInstance($this->get('created_by'));
		}
		if ($property)
		{
			$property = ($property == 'id' ? 'uidNumber' : $property);
			return $this->_creator->get($property);
		}
		return $this->_creator;
	}

	/**
	 * Was the entry reported?
	 *
	 * @return     boolean True if reported, False if not
	 */
	public function isReported()
	{
		if ($this->get('state') == 3)
		{
			$this->set('reports', 1);
		}

		if ($this->get('reports', -1) > 0)
		{
			return true;
		}
		// Reports hasn't been set
		if ($this->get('reports', -1) == -1)
		{
			if (is_file(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_support' . DS . 'tables' . DS . 'reportabuse.php'))
			{
				include_once(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_support' . DS . 'tables' . DS . 'reportabuse.php');
				$ra = new ReportAbuse($this->_db);
				$val = $ra->getCount(array(
					'id'       => $this->get('id'),
					'category' => $this->_scope,
					'state'    => 0
				));
				$this->set('reports', $val);
				if ($this->get('reports') > 0)
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get a list or count of comments
	 *
	 * @param      string  $rtrn    Data format to return
	 * @param      array   $filters Filters to apply to data fetch
	 * @param      boolean $clear   Clear cached data?
	 * @return     mixed
	 */
	public function replies($rtrn='list', $filters=array(), $clear=false)
	{
		if (!isset($filters['parent']))
		{
			$filters['parent'] = $this->get('id');
		}
		if (!isset($filters['item_type']))
		{
			$filters['item_type'] = $this->get('item_type');
		}
		if (!isset($filters['item_id']))
		{
			$filters['item_id'] = $this->get('item_id');
		}

		switch (strtolower($rtrn))
		{
			case 'count':
				if (!isset($this->_comments_count) || !is_numeric($this->_comments_count) || $clear)
				{
					$this->_comments_count = 0;

					if (!$this->_comments)
					{
						$c = $this->comments('list', $filters);
					}
					foreach ($this->_comments as $com)
					{
						$this->_comments_count++;
						if ($com->replies())
						{
							foreach ($com->replies() as $rep)
							{
								$this->_comments_count++;
								if ($rep->replies())
								{
									$this->_comments_count += $rep->replies()->total();
								}
							}
						}
					}
				}
				return $this->_comments_count;
			break;

			case 'list':
			case 'results':
			default:
				if (!($this->_comments instanceof \Hubzero\Base\ItemList) || $clear)
				{
					if ($this->get('replies', null) !== null)
					{
						$results = $this->get('replies');
					}
					else
					{
						$results = $this->_tbl->find($filters);
					}

					if ($results)
					{
						foreach ($results as $key => $result)
						{
							$results[$key] = new plgHubzeroCommentsModelComment($result);
						}
					}
					else
					{
						$results = array();
					}
					$this->_comments = new \Hubzero\Base\ItemList($results);
				}
				return $this->_comments;
			break;
		}
	}

	/**
	 * Get the contents of this entry in various formats
	 *
	 * @param      string  $as      Format to return state in [raw, parsed]
	 * @param      integer $shorten Number of characters to shorten text to
	 * @return     string
	 */
	public function content($as='parsed', $shorten=0)
	{
		$as = strtolower($as);

		switch ($as)
		{
			case 'parsed':
				if ($this->get('content.parsed') !== null)
				{
					$content = $this->get('content.parsed');
					if ($shorten)
					{
						$content = \Hubzero\Utility\String::truncate($content, $shorten, array('html' => true));
					}
					return $content;
				}

				$config = array(
					'option'   => JRequest::getCmd('option', 'com_' . $this->get('item_type')),
					'scope'    => $this->get('item_type'),
					'pagename' => $this->get('item_id'),
					'pageid'   => 0,
					'filepath' => '',
					'domain'   => ''
				);

				$content = stripslashes($this->get('content'));
				$this->importPlugin('content')->trigger('onContentPrepare', array(
					$this->_context,
					&$this,
					&$config
				));

				$this->set('content.parsed', $this->get('content'));
				$this->set('content', $content);

				return $this->content('parsed');
			break;

			case 'clean':
				$content = strip_tags($this->content('parsed'));
				if ($shorten)
				{
					$content = \Hubzero\Utility\String::truncate($content, $shorten);
				}
				return $content;
			break;

			case 'raw':
			default:
				$content = $this->get('content');
				if ($shorten)
				{
					$content = \Hubzero\Utility\String::truncate($content, $shorten);
				}
				return $content;
			break;
		}
	}

	/**
	 * Generate and return various links to the entry
	 * Link will vary depending upon action desired, such as edit, delete, etc.
	 *
	 * @param      string $type The type of link to return
	 * @return     string
	 */
	public function link($type='')
	{
		if (!isset($this->_base))
		{
			$this->_base = $this->get('url', 'index.php?option=com_' . $this->get('item_type') . '&id=' . $this->get('item_id') . '&active=comments');
		}
		$link = $this->_base;

		$s = '&';
		if (strstr($link, '?') === false)
		{
			$s = '?';
		}

		// If it doesn't exist or isn't published
		switch (strtolower($type))
		{
			case 'base':

			break;

			case 'edit':
				$link .= $s . 'editcomment=' . $this->get('id');
			break;

			case 'delete':
				$link .= $s . 'action=delete&comment=' . $this->get('id');
			break;

			case 'reply':
				$link .= $s . 'replyto=' . $this->get('id') . '#c' . $this->get('id');
			break;

			case 'report':
				$link = 'index.php?option=com_support&task=reportabuse&category=' . $this->get('item_type') . '&id=' . $this->get('id') . '&parent=' . $this->get('item_id');
			break;

			case 'permalink':
			default:
				$link .= '#c' . $this->get('id');
			break;
		}

		return $link;
	}

	/**
	 * Store changes to this database entry
	 *
	 * @param     boolean $check Perform data validation check?
	 * @return    boolean False if error, True on success
	 */
	public function store($check=true)
	{
		$this->_tbl->setUploadDir($this->get('uploadDir', '/site/comments'));

		return parent::store($check);
	}

	/**
	 * Delete the record and all associated data
	 *
	 * @return    boolean False if error, True on success
	 */
	public function delete()
	{
		// Can't delete what doesn't exist
		if (!$this->exists())
		{
			return true;
		}

		// Remove comments
		foreach ($this->replies('list') as $comment)
		{
			if (!$comment->delete())
			{
				$this->setError($comment->getError());
				return false;
			}
		}

		return parent::delete();
	}
}

