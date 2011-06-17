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

		if ($user instanceof Model_User AND $user->loaded())
		{
			// If we don't have a roll no further checking is needed
			if ( ! $role)
				return TRUE;

			if (is_array($role))
			{
				// Get all the roles
				