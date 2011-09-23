<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * Cassandra Auth driver.
 *
 * @package    jnbdz/Kohana-Cassandra
 * @author     Jean-Nicolas Boulay Desjardins
 * @copyright  (c) 2011 Jean-Nicolas Boulay Desjardins
 * @license    
 */
class Kohana_Auth_Cassandra extends Auth {

	/**
	 * Checks if a session is active.
	 *
	 * @param   mixed    $role Role name string, role ORM object, or array with role names
	 * @return  boolean
	 */
	public function logged_in($role = NULL)
	{
		// Get the user from the session
		$user = $this->get_user();

		if ( ! $user)
			return FALSE;

		// Get all the roles
		$role = $user->role;

		if (!$role)
			return FALSE;

		return TRUE;

	}

	/**
	 * Logs a user in.
	 *
	 * @param   string   username
	 * @param   string   password
	 * @param   boolean  enable autologin
	 * @return  boolean
	 */
	protected function _login($user, $password, $remember)
	{

		if ( ! is_array($user))
		{
	
			$col = (Valid::email($user)) ? 'email' : 'username';

			// Load the user
			CASSANDRA::selectColumnFamily('Users');
			$user_infos = CASSANDRA::getIndexedSlices(array($col => $user));
			$i=0;
			foreach($user_infos as $uuid => $cols) {
				$cols['uuid'] = $uuid;
				$user = $cols;
				if($i === 1)
				{	
					$this->request->redirect('error/conflic');
					Log::add(Log::ERROR, 'There was a conflic with the username and/or email. UUID: '.$user['uuid'].' username: '.$user['username'].' email: '.$user['email']);
					break;
					return;
				}
				$i++;
			}
		}

		// If the passwords match, perform a login
		if ($user['password'] === Auth::instance()->hash($password))
		{

			if ($remember === TRUE)
			{
				// Token data
				$token = sha1(uniqid(Text::random('alnum', 32), TRUE));
//die($token);
				$data = array(
					'token'	     => $token,
					'created'    => time(),
					'expires'    => time() + $this->_config['lifetime'],
					'user_agent' => sha1(Request::$user_agent),
				);

				// Create a new autologin token
				CASSANDRA::selectColumnFamily('UsersTokens')->insert($user['uuid'], $data);

				// Set the autologin cookie
				Cookie::set('authautologin', $token, $this->_config['lifetime']);
			}

			// Finish the login
			$this->complete_login($user);

			return TRUE;
		}

		// Login failed
		return FALSE;
	}

	/**
	 * Forces a user to be logged in, without specifying a password.
	 *
	 * @param   mixed    username string, or user ORM object
	 * @param   boolean  mark the session as forced
	 * @return  boolean
	 */
	public function force_login($user, $mark_session_as_forced = FALSE)
	{

		$username = $user;

		// Load the user
		CASSANDRA::selectColumnFamily('Users');
		$user_infos = CASSANDRA::getIndexedSlices(array('username' => $username));
		foreach($user_infos as $uuid => $cols) {
			$cols['uuid'] = $uuid;
			$user = $cols;
		}

		if ($mark_session_as_forced === TRUE)
		{
			// Mark the session as forced, to prevent users from changing account information
			$this->_session->set('auth_forced', TRUE);
		}

		// Run the standard completion
		$this->complete_login($user);

	}

	/**
	 * Logs a user in, based on the authautologin cookie.
	 *
	 * @return  mixed
	 */

	public function auto_login()
	{
		if ($token = Cookie::get('authautologin'))
		{
			// Load the token and user ------------> NEED TO INDEX!!!
			$usersTokens = CASSANDRA::selectColumnFamily('UsersTokens');
			$rows = CASSANDRA::getIndexedSlices(array('token' => $token));
			foreach($rows as $username => $token){}

			if (is_array($token))
			{
				if ($token['user_agent'] === sha1(Request::$user_agent))
				{

					$token = sha1(uniqid(Text::random('alnum', 32), TRUE));
					$data = array(
						'token'      => $token,
						'created'    => time(),
						'expires'    => time() + $this->_config['lifetime'],
						'user_agent' => sha1(Request::$user_agent),
					);

					// Save the token to create a new unique token
					$usersTokens->insert($uuid, $data);

					// Set the new token
					Cookie::set('authautologin', $data['token'], $data['expires'] - time());

					// Complete the login with the found data
					CASSANDRA::selectColumnFamily('Users');
					$user_infos = CASSANDRA::getIndexedSlices(array('username' => $username));
					foreach($user_infos as $uuid => $cols) {
						$cols['uuid'] = $uuid;
						$user = $cols;
					}
					$this->complete_login($user);

					// Automatic login was successful
					return $user;
				}

				// Token is invalid
				$usersTokens->remove($user['uuid']);
			}
		}

		return FALSE;

	}

	/**
         * Gets the currently logged in user from the session (with auto_login check).
         * Returns FALSE if no user is currently logged in.
         *
         * @return  mixed
         */
        public function get_user($default = NULL)
        {
                $user = parent::get_user($default);

                if ( ! $user)
                {
                        // check for "remembered" login
                        $user = $this->auto_login();
                }

                return $user;
        }

        /**
         * Log a user out and remove any autologin cookies.
         *
         * @param   boolean  completely destroy the session
         * @param       boolean  remove all tokens for user
         * @return  boolean
         */
        public function logout($destroy = FALSE, $logout_all = FALSE)
        {
                // Set by force_login()
                $this->_session->delete('auth_forced');

                if ($token = Cookie::get('authautologin'))
                {
                        // Delete the autologin cookie to prevent re-login
                        Cookie::delete('authautologin');

                        // Clear the autologin token from the database
			$usersTokens = CASSANDRA::selectColumnFamily('UsersTokens');
			$rows = CASSANDRA::getIndexedSlices(array('token' => $token));
			foreach($rows as $uuid => $token){}

                        if (is_array($token) AND $logout_all)
                        {
				$usersTokens->remove($uuid);
                        }
                        elseif (is_array($token))
                        {
				$usersTokens->remove($uuid);
                        }
                }

                return parent::logout($destroy);
        }

        /**
         * Get the stored password for a username.
         *
         * @param   mixed   username string, or user ORM object
         * @return  string
	*/
        public function password($username)
        {
		// Load the user
		CASSANDRA::selectColumnFamily('Users');
		$user_infos = CASSANDRA::getIndexedSlices(array('username' => $username));
		foreach($user_infos as $uuid => $cols) {
			$cols['uuid'] = $uuid;
			$user = $cols;
		}

		return $user['password'];
        }

        /**
         * Complete the login for a user by incrementing the logins and setting
         * session data: user_id, username, roles.
         *
         * @param   object  user ORM object
         * @return  void
         */
        protected function complete_login($user)
        {
		$model_User = new Model_User;
		$model_User->complete_login($user);
		unset($user['password']);

                return parent::complete_login((object) $user);
        }

        /**
         * Compare password with original (hashed). Works for current (logged in) user
         *
         * @param   string  $password
         * @return  boolean
         */
        public function check_password($password)
        {
                $user = $this->get_user();

                if ( ! $user)
                        return FALSE;

                return ($this->hash($password) === $user->password);
        }

}
