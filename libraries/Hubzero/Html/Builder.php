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

namespace Hubzero\Html;

use Hubzero\Error\Exception\InvalidArgumentException;

/**
 * Utility class for all HTML drawing classes
 */
class Builder
{
	/**
	 * An array to hold method references
	 *
	 * @var  array
	 */
	protected static $registry = array();

	/**
	 * Class loader method
	 *
	 * Additional arguments may be supplied and are passed to the sub-class.
	 * Additional include paths are also able to be specified for third-party use
	 *
	 * @param   string  $method
	 * @param   array   $parameters
	 * @return  mixed
	 */
	public function __call($method, $parameters)
	{
		$func = array_shift($parameters);
		$key  = $method . '.' . $func;

		if (!array_key_exists($key, static::$registry))
		{
			$cls  = __NAMESPACE__ . '\\Builder\\' . ucfirst($method);

			if (!class_exists($cls))
			{
				throw new InvalidArgumentException(sprintf('%s %s not found.', $cls, $func), 500);
			}

			$callable = array($cls, $func);

			if (!is_callable($callable))
			{
				throw new InvalidArgumentException(sprintf('%s %s not found.', $cls, $func), 500);
			}

			$this->register($key, $callable);
		}

		$function = static::$registry[$key];

		return call_user_func_array($function, $parameters);
	}

	/**
	 * Registers a function to be called with a specific key
	 *
	 * @param   string   $key       The name of the key
	 * @param   array    $callable  Function or method
	 * @return  boolean  True if the function is callable
	 */
	public function register($key, $callable)
	{
		if (!$this->has($key) && is_callable($callable))
		{
			self::$registry[$key] = $callable;
			return true;
		}

		return false;
	}

	/**
	 * Removes a key for a method from registry.
	 *
	 * @param   string   $key  The name of the key
	 * @return  boolean  True if a set key is unset
	 */
	public function forget($key)
	{
		if (isset(self::$registry[$key]))
		{
			unset(self::$registry[$key]);
			return true;
		}

		return false;
	}

	/**
	 * Test if the key is registered.
	 *
	 * @param   string   $key  The name of the key
	 * @return  boolean  True if the key is registered.
	 */
	public function has($key)
	{
		return isset(self::$registry[$key]);
	}
}
