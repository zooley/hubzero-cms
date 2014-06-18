<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
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
 * @author    David Benham <dbenham@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Hubzero\Mail;

use RuntimeException;
use HubmailConfig;

/**
 * Hubzero library class for creating a unique token to
 * include in emails
 */
class Token
{
	/**
	 * Description for 'mailTokenTicket'
	 */
	const emailTokenTicket = 1;

	/**
	 * Description for 'mailTokenGroupThread'
	 */
	const emailTokenGroupThread = 2;

	/**
	 * Description for '_currentVersion'
	 *
	 * @var string
	 */
	private $_currentVersion;

	/**
	 * Description for '_iv'
	 *
	 * @var unknown
	 */
	private $_iv;

	/**
	 * Description for '_key'
	 *
	 * @var unknown
	 */
	private $_key;

	/**
	 * Description for '_blocksize'
	 *
	 * @var number
	 */
	private $_blocksize;

	/**
	 * Read encryption configuration from config file
	 *
	 * @return void
	 */
	public function __construct()
	{
		$config = \JFactory::getConfig();

		if (empty($config))
		{
			throw new RuntimeException(__CLASS__ . '::__construct(); failed JFactory::getConfig() call');
		}

		$file = '/etc/hubmail_gw.conf';

		if (file_exists($file))
		{
			include_once($file);
		}
		else
		{
			throw new RuntimeException(sprintf('File "%s" does not exist', $file));
		}

		// HubmailConfig is defined here
		include_once('/etc/hubmail_gw.conf');

		if (!class_exists('HubmailConfig'))
		{
			throw new RuntimeException('Class HubmailConfig not loaded');
		}
		else
		{
			$HubmailConfig1 = new HubmailConfig();
		}

		// Get current token version
		$this->_currentVersion = $HubmailConfig1->email_token_current_version;

		if (empty($this->_currentVersion))
		{
			throw new RuntimeException('Class HubmailConfig->email_token_current_version not found in config file');
		}

		// Grab the encryption info for that version
		$prop = 'email_token_encryption_info_v' . $this->_currentVersion;
		$encryption_info = $HubmailConfig1->$prop;

		if (empty($encryption_info))
		{
			throw new RuntimeException('Class HubmailConfig->email_token_encryption_info_vX not found for version: ' . $this->_currentVersion);
		}

		// Encryption info is comma delimited (key, iv) in this configuraiton value
		$keyArray = explode(',', $encryption_info);

		if (count($keyArray) <> 2)
		{
			throw new RuntimeException(__CLASS__ . '::__construct(); config.email_token_encryption_info_v' . $tokenVersion . ' cannot be split');
		}

		$this->_key = $keyArray[0];
		$this->_iv  = $keyArray[1];
		$this->_blocksize = 8; // in bytes
	}

	/**
	 * Build a unique email token
	 *
	 * @param  int    $version
	 * @param  int    $action
	 * @param  int    $userid
	 * @param  int    $id
	 * @return string - base 16 string representing token
	 */
	public function buildEmailToken($version, $action, $userid, $id)
	{
		$rv = '';

		$binaryString = pack("NNN", $userid, $id, intval(time()));

		// Hash the unencrypted version hex version of the binary string
		// Include the unencrypted version and action bytes as well
		$hash = sha1(bin2hex(pack("C", $version)) . bin2hex(pack("C", $action)) .  bin2hex($binaryString));

		// We're only using a portion of the hash as a checksum
		$hashsub = substr($hash, 0, 4);

		// Append hash to end of binary string, two hex digits stuffed into a single unsigned byte
		$binaryString .= pack("n", hexdec($hashsub));

		// Add PKCS7 style padding before encryption
		$pad = $this->_blocksize - (strlen($binaryString) % $this->_blocksize);
		$binaryString .= str_repeat(chr($pad), $pad);

		// Do the encryption
		$cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
		mcrypt_generic_init($cipher, $this->_key, $this->_iv);
		$encrypted = mcrypt_generic($cipher, $binaryString);
		mcrypt_generic_deinit($cipher);

		// Prepend an unencrypted version byte and action byte (in base16)
		$rv = bin2hex(pack("C", $version)) . bin2hex(pack("C", $action)) .  bin2hex($encrypted);

		return $rv;
	}
}

