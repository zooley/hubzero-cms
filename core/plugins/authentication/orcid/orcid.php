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
 * @author    Sam Wilson <samwilson@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// No direct access
defined('_HZEXEC_') or die();

use Orcid\Profile;
use Orcid\Oauth;

class plgAuthenticationOrcid extends \Hubzero\Plugin\OauthClient
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var boolean
	 */
	protected $_autoloadLanguage = true;

	/**
	 * Perform logout
	 *
	 * @return  void
	 */
	public function logout()
	{
		// Not supported by ORCID
	}

	/**
	 * Check login status of current user with regards to ORCID
	 *
	 * @return  array  $status
	 */
	public function status()
	{
		// Not supported by ORCID
	}

	/**
	 * Method to call when redirected back from ORCID after authentication
	 * Grab the return URL if set and handle denial of app privileges from ORCID
	 *
	 * @param   object  $credentials
	 * @param   object  $options
	 * @return  void
	 */
	public function login(&$credentials, &$options)
	{
		$b64dreturn = '';

		// Check the state for our return variable
		if ($return = Request::getVar('state', '', 'method', 'base64'))
		{
			$b64dreturn = base64_decode($return);
			if (!JURI::isInternal($b64dreturn))
			{
				$b64dreturn = '';
			}
		}

		$options['return'] = $b64dreturn;

		// If we have a code coming back, the user has authorized our app, and we can authenticate
		if (!Request::getVar('code', NULL))
		{
			// User didn't authorize our app or clicked cancel
			App::redirect(
				Route::url('index.php?option=com_users&view=login&return=' . $return),
				Lang::txt('PLG_AUTHENTICATION_ORCID_MUST_AUTHORIZE_TO_LOGIN', Config::get('sitename')),
				'error'
			);
		}
	}

	/**
	 * Sets up ORCID params and redirects to ORCID authorize URL
	 *
	 * @param   object  $view  view object
	 * @param   object  $tpl   template object
	 * @return  void
	 */
	public function display($view, $tpl)
	{
		// Set up the config for the ORCID api instance
		$oauth = new Oauth;
		$oauth->setClientId($this->params->get('client_id'))
		      ->setScope('/authenticate')
		      ->setState($view->return)
		      ->showLogin()
		      ->setRedirectUri(self::getRedirectUri('orcid'));

		// If we're linking an account, set any info that we might already know
		if (!User::isGuest())
		{
			$profile = \Hubzero\User\Profile::getInstance(User::get('id'));
			$oauth->setEmail(User::get('email'));
			$oauth->setFamilyNames($profile->get('surname'));
			$oauth->setGivenNames($profile->get('givenName'));
		}

		// Create and follow the authorization URL
		App::redirect($oauth->getAuthorizationUrl());
	}

	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @param   array    $credentials  Array holding the user credentials
	 * @param   array    $options      Array of extra options
	 * @param   object   $response     Authentication response object
	 * @return  boolean
	 */
	public function onUserAuthenticate($credentials, $options, &$response)
	{
		// Set up the config for the ORCID api instance
		$oauth = new Oauth;
		$oauth->setClientId($this->params->get('client_id'))
		      ->setClientSecret($this->params->get('client_secret'))
		      ->setRedirectUri(self::getRedirectUri('orcid'));

		// Authenticate the user
		$oauth->authenticate(Request::getVar('code'));

		// Check for successful authentication
		if ($oauth->isAuthenticated())
		{
			$orcid = new Profile($oauth);

			// Set username to ORCID iD
			$username = $orcid->id();

			// Create the hubzero auth link
			$method = (Component::params('com_users')->get('allowUserRegistration', false)) ? 'find_or_create' : 'find';
			$hzal = \Hubzero\Auth\Link::$method('authentication', 'orcid', null, $username);

			if ($hzal === false)
			{
				$response->status = \Hubzero\Auth\Status::FAILURE;
				$response->error_message = Lang::txt('PLG_AUTHENTICATION_ORCID_UNKNOWN_USER');
				return;
			}

			$hzal->email = $orcid->email() ? $orcid->email() : null;

			// Set response variables
			$response->auth_link = $hzal;
			$response->type      = 'orcid';
			$response->status    = \Hubzero\Auth\Status::SUCCESS;
			$response->fullname  = $orcid->fullName();

			if (!empty($hzal->user_id))
			{
				$user = User::getInstance($hzal->user_id);

				$response->username = $user->username;
				$response->email    = $user->email;
				$response->fullname = $user->name;
			}
			else
			{
				$response->username = '-' . $hzal->id;
				$response->email    = $response->username . '@invalid';

				// Also set a suggested username for their hub account
				Session::set('auth_link.tmp_username', str_replace(' ', '', strtolower($response->fullname)));
				Session::set('auth_link.tmp_orcid', $username);
			}

			$hzal->update();

			// If we have a real user, drop the authenticator cookie
			if (isset($user) && is_object($user))
			{
				// Set cookie with login preference info
				$prefs = array(
					'user_id'       => $user->get('id'),
					'user_img'      => null,
					'authenticator' => 'orcid'
				);

				$namespace = 'authenticator';
				$lifetime  = time() + 365*24*60*60;

				\Hubzero\Utility\Cookie::bake($namespace, $lifetime, $prefs);
			}
		}
		else
		{
			$response->status = \Hubzero\Auth\Status::FAILURE;
			$response->error_message = Lang::txt('PLG_AUTHENTICATION_ORCID_AUTHENTICATION_FAILED');
		}
	}

	/**
	 * Similar to onAuthenticate, except we already have a logged in user, we're just linking accounts
	 *
	 * @param   array  $options
	 * @return  void
	 */
	public function link($options=array())
	{
		// Set up the config for the ORCID api instance
		$oauth = new Oauth;
		$oauth->setClientId($this->params->get('client_id'))
		      ->setClientSecret($this->params->get('client_secret'))
		      ->setRedirectUri(self::getRedirectUri('orcid'));

		// If we have a code coming back, the user has authorized our app, and we can authenticate
		if (!Request::getVar('code', NULL))
		{
			// User didn't authorize our app, or, clicked cancel...
			App::redirect(
				Route::url('index.php?option=com_members&id=' . User::get('id') . '&active=account'),
				Lang::txt('PLG_AUTHENTICATION_ORCID_MUST_AUTHORIZE_TO_LINK', Config::get('sitename')),
				'error'
			);
		}

		// Authenticate the user
		$oauth->authenticate(Request::getVar('code'));

		// Check for successful authentication
		if ($oauth->isAuthenticated())
		{
			$orcid = new Profile($oauth);

			// Set username to ORCID iD
			$username = $orcid->id();

			$hzad = \Hubzero\Auth\Domain::getInstance('authentication', 'orcid', '');

			// Create the link
			if (\Hubzero\Auth\Link::getInstance($hzad->id, $username))
			{
				// This orcid account is already linked to another hub account
				App::redirect(
					Route::url('index.php?option=com_members&id=' . User::get('id') . '&active=account'),
					Lang::txt('PLG_AUTHENTICATION_ORCID_ACCOUNT_ALREADY_LINKED'),
					'error'
				);
			}
			else
			{
				// Create the hubzero auth link
				$hzal = \Hubzero\Auth\Link::find_or_create('authentication', 'orcid', null, $username);
				$hzal->user_id = User::get('id');
				$hzal->email   = $orcid->email();
				$hzal->update();
			}
		}
		else
		{
			// User didn't authorize our app, or, clicked cancel...
			App::redirect(
				Route::url('index.php?option=com_members&id=' . User::get('id') . '&active=account'),
				Lang::txt('PLG_AUTHENTICATION_ORCID_MUST_AUTHORIZE_TO_LINK', Config::get('sitename')),
				'error'
			);
		}
	}
}