<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

/**
 * Wiki macro class for linking contributor
 */
class ContributorMacro extends WikiMacro
{
	/**
	 * Returns description of macro, use, and accepted arguments
	 *
	 * @return  array
	 */
	public function description()
	{
		$txt = array();
		$txt['wiki'] = 'This macro will generate a link to a contributor\'s page with the contributor\'s name as the link text. It accepts either the contributor\'s ID, username, or name. NOTE: to use a name, it must be identical to their contributor page.';
		$txt['html'] = '<p>This macro will generate a link to a contributor\'s page with the contributor\'s name as the link text. It accepts either the contributor\'s ID, username, or name. NOTE: to use a name, it must be identical to their contributor page.</p>';
		return $txt['html'];
	}

	/**
	 * Generate macro output
	 *
	 * @return  string
	 */
	public function render()
	{
		$et = $this->args;

		if (!$et)
		{
			return '';
		}
		$id = 0;
		// Is it numeric?
		if (is_numeric($et))
		{
			// Yes, then get contributor by ID
			$id = intval($et);
			$sql = "SELECT id, givenName, middleName, surname, name FROM `#__users` WHERE id=" . $id;
			// Perform query
			$this->_db->setQuery($sql);
			$a = $this->_db->loadRow();

			// Did we get a result from the database?
			if ($a)
			{
				$id = ($id) ? $id : $a[0];
				// Build and return the link
				if ($a[4] != '')
				{
					$name = $a[4];
				}
				else
				{
					$name  = $a[1] . ' ';
					$name .= ($a[2]) ? $a[2] . ' ' : '';
					$name .= $a[3] . ' ';
				}
			}
		}
		else
		{
			// No, it could be username or name
			$n = trim($et);
			// Is there a space in it inidcating name ("First Last")?
			if (!strpos($n, ' '))
			{
				// No, then we must have a username
				// Get user's name
				$cuser = User::getInstance($n);
				if (is_object($cuser))
				{
					$name = $cuser->get('name');
					$id   = $cuser->get('id');
				}
				else
				{
					return '(contributor:' . $et . ' not found)';
				}
			}
			else
			{
				$bits = explode(' ', $n);
				$sql = "SELECT id, givenName, middleName, surname, name FROM `#__users` WHERE givenName=" . $this->_db->quote($bits[0]) . " AND surname=" . $this->_db->quote(end($bits));
				// Perform query
				$this->_db->setQuery($sql);
				$a = $this->_db->loadRow();

				// Did we get a result from the database?
				if ($a)
				{
					$id = ($id) ? $id : $a[0];
					// Build and return the link
					if ($a[4] != '')
					{
						$name = $a[4];
					}
					else
					{
						$name  = $a[1] . ' ';
						$name .= ($a[2]) ? $a[2] . ' ' : '';
						$name .= $a[3] . ' ';
					}
				}
			}
		}

		// Did we get a result from the database?
		if ($name && $id)
		{
			return '<a href="' . Route::url('index.php?option=com_members&id=' . $id) . '">' . $name . '</a>';
			//return '['.Route::url('index.php?option=com_members&id='.$id).' '.$name.']';
		}
		else
		{
			// Return error message
			return '(contributor:' . $et . ' not found)';
		}
	}
}
