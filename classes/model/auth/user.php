<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * Default auth user
 *
 * @package    Kohana/Auth
 * @author     Kohana Team
 * @copyright  (c) 2007-2011 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Model_Auth_User {

	/**
	 * A user has many tokens and roles
	 *
	 * @var array Relationhips
	 */
	protected $_has_many = array(
		'user_tokens' => array('model' => 'user_token'),
		'roles'       => array('model' => 'role', 'through' => 'roles_users'),
	);

	/**
	 * Rules for the user model. Because the password is _always_ a hash
	 * when it's set,you need to run an additional not_empty rule in your controller
	 * to make sure you didn't hash an empty string. The password rules
	 * should be enforced outside the model or with a model helper method.
	 *
	 * @return array Rules
	 */
	protected $_rules = array(
		'username' => array(
			'not_empty' => NULL,
			'min_length' => array(4),
			'max_length' => array(32),
			'regex' => array('/^[-\pL\pN_.]++$/uD'),	
		),
		'password' => array(
			'not_empty' => NULL,
			'min_length' => array(8),
			'max_length' => array(42),
		),
		'password_confirm' => array(
			'matches' => array('password'),
		),
		'email' => array(
			'not_empty' => NULL,
			'min_length' => array(4),
			'max_length' => array(127),
			'validate::email' => NULL,
		),
	);

	/**
	 * Labels for fields in this model
	 *
	 * @return array Labels
	 */
	protected $_labels = array(
		'username'         => 'username',
		'email'            => 'email address',
		'password'         => 'password',
	);

	/**
	 * Complete the login for a user by incrementing the logins and saving login timestamp
	 *
	 * @return void
	 */
	public function complete_login($user)
	{
		CASSANDRA::selectColumnFamily('Users')->insert($user['uuid'], array(
									'logins'	=> $user['logins'] + 1,
									'last_login'	=> date('YmdHis', time()),
								));
	}

	/**
	 * Does the reverse of row_key_exists() by triggering error if username exists.
	 * Validation callback.
	 *
	 * @param   Validation  Validation object
	 * @param   string      Field name
	 * @return  void
	 */
	public function username_available(Validation $validation, $field)
	{
		if ($this->row_key_exists('username', $validation[$field]))
		{
			$validation->error($field, 'username_available', array($validation[$field]));
		}
	}

	/**
	 * Does the reverse of unique_key_exists() by triggering error if email exists.
	 * Validation callback.
	 *
	 * @param   Validation  Validation object
	 * @param   string      Field name
	 * @return  void
	 */
	public function email_available(Validation $validation, $field)
	{
		if ($this->row_key_exists('email', $validation[$field]))
		{
			$validation->error($field, 'email_available', array($validation[$field]));
		}
	}

	public function row_key_exists($what, $value)
	{
		CASSANDRA::selectColumnFamily('Users');
		$infos = CASSANDRA::getIndexedSlices(array($what => $value));

		$info = FALSE;
		foreach ($infos as $k => $v) {
			$info = $k;
		}

		return (bool) $info;
	}

	/**
	 * Adds the Users ColumnFamily
	 *
	 * @param array $fields
	 * @param string $username
	 * @return the timestamp for the operation
	 */
	public function create_user($fields)
	{
		Validation::factory($fields)
			->rules('username', $this->_rules['username'])
			->rule('username', 'username_available', array($this, ':field'))
			->rules('email', $this->_rules['email'])
			->rule('email', 'email_available', array($this, ':field'))
			->rules('password', $this->_rules['password'])
			->rules('password_confirm', $this->_rules['password_confirm']);
			//->labels($_labels);

		if (Kohana::config('useradmin')->activation_code) {
			Validation::factory($fields)->rule('activation_code', 'check_activation_code', array($this, ':field'));
		}

		// Generate a unique ID
		$uuid = CASSANDRA::Util()->uuid1();

		//CASSANDRA::selectColumnFamily('UsersRoles')->insert($username, array('rolename' => 'login'));
		CASSANDRA::selectColumnFamily('Users')->insert($uuid, array(
								'username'		=> $fields['username'],
								'email'			=> $fields['email'],
								'password'		=> Auth::instance()->hash($fields['password']),
								'logins'		=> 0,
								'last_login'		=> 0,
								'last_failed_login'	=> 0,
								'failed_login_count'	=> 0,
								'created'		=> date('YmdHis', time()),
								'modify'		=> 0,
								'role'			=> 'login',
								'email_verified'	=> $fields['email_code'],
							));
	}

	/**
	 * Verifies that the code for email confirmation is good
	 *
	 * @param   Validation  Validation object
	 * @param   String 	Field Name
	 * @return  Validation 
	 */
	public function email_confirmation_code(Validation $validation, $field)
	{
		if(Auth::instance()->get_user()->email_verified !== $field)
		{
			$validation->error($field, 'email_confirmation_code', array($validation[$field]));
		}
	}

	/**
	 * Update the email_verified column to true
	 *
	 * @param None
	 * @return unknown
	 */
	public function update_email_confirmation()
	{
		CASSANDRA::selectColumnFamily('Users')->insert(Auth::instance()->get_user()->uuid, array(
								'email_verified'	=> 'true',
								'modify'		=> date('YmdHis', time()),
							));

		$user_infos = Auth::instance()->get_user();
		$user_infos->email_verified = 'true';
		$user_infos->modify = date('YmdHis', time());

		$config = Kohana::$config->load('auth');
		Session::instance($config->session_type)->set($config->session_key, $user_infos);
	}

	/**
	 * Updates the Users ColumnFamily
	 *
	 * @param array $fields
	 * @param string $username
	 * @return the timestamp for the operation
	 */
	public function update_user($fields)
	{
		Validation::factory($fields)
			->rules('username', $this->_rules['username'])
			->rules('email', $this->_rules['email']);


		$user_infos = Auth::instance()->get_user();

		$update = array(
					'username'              => $fields['username'],
					'email'                 => $fields['email'],
					'modify'                => date('YmdHis', time()),
					'email_verified'        => $fields['email_code'],
				);

		if(isset($fields['password']))
		{

			Validation::factory($fields)
				->rules('password', $this->_rules['password'])
				->rules('password_confirm', $this->_rules['password_confirm']);

			$update = array_merge($update, array('password' => Auth::instance()->hash($fields['password'])));
		}

		CASSANDRA::selectColumnFamily('Users')->insert($user_infos->uuid, $update);

		$user_infos->username = $fields['username'];
		$user_infos->email = $fields['email'];
		$user_infos->email_verified = $fields['email_code'];
		$user_infos->modify = date('YmdHis', time());

		// Update session because it use via Auth::instance()->get_user() to set data in the view
		$config = Kohana::$config->load('auth');
		Session::instance($config->session_type)->set($config->session_key, $user_infos);

		return TRUE;
	}

	/**
	 * Delete user
	 *
	 * @param unknown uuid
	 * @return unknown
	 */
	public function delete_user($uuid)
	{
		// Validation
		CASSANDRA::selectColumnFamily('Users')->remove($uuid);
	}

	/**
	 * Get activation code
	 *
	 * @param string email
	 * @return array information related to the activation code
	 */
	public function get_activation_code_with_email($email)
	{
		CASSANDRA::selectColumnFamily('UsersActivationCode');
		$user_act_code_info = CASSANDRA::getIndexedSlices(array('invited_user_email' => $email));
		foreach ($user_act_code_info as $uuid => $cols)
		{
			$cols['uuid'] = $uuid;
			return $cols;
			break;
		}
	}

	/**
	 * Check if the activation code is register
	 *
	 * @param string activation_code
	 * @return bool
	 */
	public function check_activation_code(Validation $validation, $field)
	{
		CASSANDRA::selectColumnFamily('UsersActivationCode');
		$user_act_code_info = CASSANDRA::getIndexedSlices(array('activation_code' => $activation_code));
		foreach ($user_act_code_info as $uuid => $cols) {}
		if (!$uuid)
		{
			$validation->error($field, 'check_activation_code', array($validation[$field]));
		}
	}

	/**
	 * Generate activation code
	 *
	 * @param array user
	 * @return bool shows if everything went well
	 */
	public function add_activation_code($user, $email, $activation_code)
	{

		if (Kohana::$config->load('useradmin')->get('activation_code_user_role') !== 'login')
		{
			if ($user['role'] !== Kohana::$config->load('useradmin')->get('activation_code_user_role'))
			{
				return FALSE;
			}
		}

		if (Kohana::$config->load('useradmin')->get('activation_code_limit') > 0)
		{
			// Limit user invitations number

			if ((Kohana::$config->load('useradmin')->get('activation_code_limit') - $user['activation_code_num']) === 0)
			{
				return FALSE;
			}

			CASSANDRA::selectColumnFamily('Users')->insert($user['uuid'], array(
									'activation_code_num'	=> $user['activation_code_num'] + 1,
								));
		}

		$uuid = CASSANDRA::Util()->uuid1();
		// Add activation code for invited user
		CASSANDRA::selectColumnFamily('UsersActivationCode')->insert($uuid, array(
										'host_of_activ_code'	=> $user['uuid'],
										'invited_user_email'	=> $email,
										'activation_code'	=> $activation_code,
									));

		return TRUE;
	}

	/**
	 * Remove activation code
	 *
	 * @param array user
	 * @return nothing
	 */
	public function remove_activation_code($user, $email)
	{
		$act_code_info = $this->get_activation_code_with_email($email);
		CASSANDRA::selectColumnFamily('UsersActivationCode')->remove($act_code_info['uuid']);

		if (Kohana::$config->load('useradmin')->get('activation_code_limit') > 0)
		{
			CASSANDRA::selectColumnFamily('Users')->insert($user['uuid'], array(
									'activation_code_num'	=> $user['activation_code_num'] - 1,
								));
		}
	}

} // End Auth User Model
