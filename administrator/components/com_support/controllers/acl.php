<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Support\Controllers;

use Components\Support\Helpers\ACL as TheACL;
use Components\Support\Tables\Aro;
use Components\Support\Tables\Aco;
use Components\Support\Tables\AroAco;
use Hubzero\Component\AdminController;
use Exception;

/**
 * Support controller class for defining permissions
 */
class Acl extends AdminController
{
	/**
	 * Displays a list of records
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		// Instantiate a new view
		$this->view->acl = TheACL::getACL();
		$this->view->database = $this->database;

		// Fetch results
		$aro = new Aro($this->database);
		$this->view->rows = $aro->getRecords();

		// Output HTML
		$this->view->display();
	}

	/**
	 * Update an existing record
	 *
	 * @return  void
	 */
	public function updateTask()
	{
		// Check for request forgeries
		//\JRequest::checkToken('get') or jexit('Invalid Token');

		$id     = \JRequest::getInt('id', 0);
		$action = \JRequest::getVar('action', '');
		$value  = \JRequest::getInt('value', 0);

		$row = new AroAco($this->database);
		$row->load($id);

		switch ($action)
		{
			case 'create': $row->action_create = $value; break;
			case 'read':   $row->action_read   = $value; break;
			case 'update': $row->action_update = $value; break;
			case 'delete': $row->action_delete = $value; break;
		}

		// Check content
		if (!$row->check())
		{
			throw new Exception($row->getError(), 500);
		}

		// Store new content
		if (!$row->store())
		{
			throw new Exception($row->getError(), 500);
		}

		// Output messsage and redirect
		$this->setRedirect(
			\JRoute::_('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			\JText::_('COM_SUPPORT_ACL_SAVED')
		);
	}

	/**
	 * Delete one or more records
	 *
	 * @return	void
	 */
	public function removeTask()
	{
		// Check for request forgeries
		\JRequest::checkToken() or jexit('Invalid Token');

		$ids = \JRequest::getVar('id', array());

		foreach ($ids as $id)
		{
			$row = new Aro($this->database);
			$row->load(intval($id));

			if ($row->id)
			{
				$aro_aco = new AroAco($this->database);
				if (!$aro_aco->deleteRecordsByAro($row->id))
				{
					throw new Exception($aro_aco->getError(), 500);
				}
			}

			if (!$row->delete())
			{
				throw new Exception($row->getError(), 500);
			}
		}

		// Output messsage and redirect
		$this->setRedirect(
			\JRoute::_('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			\JText::_('COM_SUPPORT_ACL_REMOVED')
		);
	}

	/**
	 * Save a new record
	 *
	 * @return  void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		\JRequest::checkToken() or jexit('Invalid Token');

		// Trim and addslashes all posted items
		$aro = \JRequest::getVar('aro', array(), 'post');
		$aro = array_map('trim', $aro);

		// Initiate class and bind posted items to database fields
		$row = new Aro($this->database);
		if (!$row->bind($aro))
		{
			throw new Exception($row->getError(), 500);
		}

		if ($row->foreign_key)
		{
			switch ($row->model)
			{
				case 'user':
					$user = \JUser::getInstance($row->foreign_key);
					if (!is_object($user))
					{
						throw new Exception(\JText::_('COM_SUPPORT_ACL_ERROR_UNKNOWN_USER'), 500);
					}
					$row->foreign_key = intval($user->get('id'));
					$row->alias = $user->get('username');
				break;

				case 'group':
					$group = \Hubzero\User\Group::getInstance($row->foreign_key);
					if (!is_object($group))
					{
						throw new Exception(\JText::_('COM_SUPPORT_ACL_ERROR_UNKNOWN_GROUP'), 500);
					}
					$row->foreign_key = intval($group->gidNumber);
					$row->alias = $group->cn;
				break;
			}
		}

		// Check content
		if (!$row->check())
		{
			throw new Exception($row->getError(), 500);
		}

		// Store new content
		if (!$row->store())
		{
			throw new Exception($row->getError(), 500);
		}

		if (!$row->id)
		{
			$row->id = $this->database->insertid();
		}

		// Trim and addslashes all posted items
		$map = \JRequest::getVar('map', array(), 'post');

		foreach ($map as $k => $v)
		{
			// Initiate class and bind posted items to database fields
			$aroaco = new AroAco($this->database);
			if (!$aroaco->bind($v))
			{
				throw new Exception($aroaco->getError(), 500);
			}
			$aroaco->aro_id = (!$aroaco->aro_id) ? $row->id : $aroaco->aro_id;

			// Check content
			if (!$aroaco->check())
			{
				throw new Exception($aroaco->getError(), 500);
			}

			// Store new content
			if (!$aroaco->store())
			{
				throw new Exception($aroaco->getError(), 500);
			}
		}

		// Output messsage and redirect
		$this->setRedirect(
			\JRoute::_('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			\JText::_('COM_SUPPORT_ACL_SAVED')
		);
	}
}
