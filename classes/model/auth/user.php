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
	public function rules()
	{
		return array(
			'username' => array(
				array('not_empty'),
				array('min_length', array(':value', 4)),
				array('max_length', array(':value', 32)),
				array('regex', array(':value', '/^[-\pL\pN_.]++$/uD')),
				array(array($this, 'username_available'), array(':validation', ':field')),
			),
			'password' => array(
				array('not_empty'),
				array('min_length', array(':value', 8)),
			),
			'password_confirm' => array(
				array('matches', array(':validation', ':field', 'password')),
			),
			'email' => array(
				array('not_empty'),
				array('min_length', array(':value', 4)),
				array('max_length', array(':value', 127)),
				array('email'),
				array(array($this, 'email_available'), array(':validation', ':field')),
			),
		);
	}

	/**
	 * Filters to run when data is set in this model. The password filter
	 * automatically hashes the password when it's set in the model.
	 *
	 * @return array Filters
	 */
	public function filters()
	{
		return array(
			'password' => array(
				array(array(Auth::instance(), 'hash'))
			)
		);
	}

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
	public function complete_login($username)
	{

		$users = CASSANDRA::selectColumnFamily('Users');

		$user = $users->get($username);

		$users->insert($username, array('logins' => $user['logins'] + 1, 'last_login' => time())); 

	}

	/**
	 * Does the reverse of unique_key_exists() by triggering error if username exists.
	 * Validation callback.
	 *
	 * @param   Validation  Validation object
	 * @param   string      Field name
	 * @return  void
	 */
	public function username_available(Validation $validation, $field)
	{
		if ($this->row_key_exists($validation[$field]))
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
		if (!Valid::email($validation[$field]))
		{
			$validation->error($field, 'email', array($validation[$field]));
		} elseif ($this->unique_key_exists('email', $validation[$field]))
		{
			$validation->error($field, 'email_available', array($validation[$field]));
		}
	}

	public function row_key_exists($value)
	{
		$user = CASSANDRA::selectColumnFamily('Users');
		return (bool) $user->get_count($value);
	}

	/**
	 * Tests if a unique key value exists in the database.
	 *
	 * @param   mixed    the value to test
	 * @param   string   field name
	 * @return  boolean
	 */
	public function unique_key_exists($col, $value)
	{
		CASSANDRA::selectColumnFamily('Users');
		return (bool) CASSANDRA::getIndexedSlices(array($col => $value));
	}

	/**
	 * Validate method validates the form for subscription
	 *
	 * @param array $values
	 * @return Validation
	 */
	public function validate($values)
	{
		return Validation::factory($values)
			->rules(self::rules())
			->filters(self::filters());
	}

	/**
	 * Adds or updates the Users ColumnFamily
	 *
	 * @param array $fields
	 * @param string $username
	 * @return the timestamp for the operation
	 */
	public static function create_user($fields, $username)
	{
		$this->validate($fields);
		die('Its here!');
		CASSANDRA::selectColumnFamily('UsersRoles')->insert($username, array('rolename' => 'login'));
		return CASSANDRA::selectColumnFamily('Users')->insert($username, array('email' => $fields['email'], 'password' => $fields['password']));
	}

	/**
	 * Updates the Users ColumnFamily
	 *
	 * @param array $fields
	 * @param string $username
	 * @return the timestamp for the operation
	 */
	public function update_user($fields, $username)
	{
		if (empty($fields['password']))
		{
			// Send Error!
		}

		$this->validate($fields);
		$users = CASSANDRA::selectColumnFamily('Users');
		if ($users->get_cout($username))
		{
			return $users->insert($username, array('password' => $fields['password']));
		}
		else
		{
			// Send Error!
		}
	}

} // End Auth User Model
