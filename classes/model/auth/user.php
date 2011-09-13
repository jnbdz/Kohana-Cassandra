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
	public function labels()
	{
		return array(
			'username'         => 'username',
			'email'            => 'email address',
			'password'         => 'password',
		);
	}

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
	 * Adds or updates the Users ColumnFamily
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
							));
		return TRUE;
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
			->rule('username', 'username_available', array($this, ':field'))
			->rules('email', $this->_rules['email'])
			->rule('email', 'email_available', array($this, ':field'))
			->rules('password', $this->_rules['password'])
			->rules('password_confirm', $this->_rules['password_confirm']);	

		$user_infos = Auth::instance()->get_user();

		CASSANDRA::selectColumnFamily('Users')->insert($user_infos->uuid, array(
								'username'      => $fields['username'],
								'password'	=> Auth::instance()->hash($fields['password']),
								'email'		=> $fields['email'],
								'modify'	=> date('YmdHis', time()),
							));
		return TRUE;
	}

	/**
	 * Delete user
	 *
	 * @param unknown user
	 * @return unknown
	 */
	public function delete_user($uuid)
	{
		// Validation
		CASSANDRA::selectColumnFamily('Users')->remove($uuid);
	}

} // End Auth User Model
