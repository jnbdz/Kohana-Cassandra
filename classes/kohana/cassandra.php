<?php defined('SYSPATH') or die('No direct script access.');
/**
* Cassandra
*
* @package        Cassandra
* @author         Jean-Nicolas Boulay Desjardins
* @copyright      (c) 2011 Jean-Nicolas Boulay Desjardins
* @license        http://www.opensource.org/licenses/isc-license.txt
*/

class Kohana_CASSANDRA {

	protected static $config = array();
	protected static $keyspace = NULL;
	protected static $servers = array();
	public static $pool = NULL;

/*	public function __call()
	{

		require_once ('phpcassa/connection.php');
		require_once ('phpcassa/columnfamily.php');

	}
*/	
	public function __construct()
	{

		require ('phpcassa/connection.php');
		require ('phpcassa/columnfamily.php');

		// Test the config group name
		$config = Kohana::config('cassandra');

		self::$servers = $config['servers'];
		self::$keyspace = $config['keyspace'];

		self::$pool = new ConnectionPool($this->keyspace, $this->servers);

	}

	public static function selectColumnFamily($column_family_name)
	{

		return new ColumnFamily(self::$pool, $column_family_name);

	}

} // End of Cassandra
