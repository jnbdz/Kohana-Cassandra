<?php defined('SYSPATH') or die('No direct script access.');
/**
* Cassandra
*
* @package        Cassandra
* @author         Jean-Nicolas Boulay Desjardins
* @copyright      (c) 2011 Jean-Nicolas Boulay Desjardins
* @license        http://www.opensource.org/licenses/isc-license.txt
*/

#require ('phpcassa/connection.php');
#require ('phpcassa/columnfamily.php');

class Kohana_CASSANDRA {

	protected static $config = array();
	protected static $keyspace = NULL;
	protected static $servers = array();
	public static $pool = NULL;

	public function __construct()
	{

require_once ('phpcassa/connection.php');

		// Test the config group name
		$config = Kohana::config('cassandra');

		self::$servers = $config['servers'];
		self::$keyspace = $config['keyspace'];

		self::$pool = new ConnectionPool($this->keyspace, $this->servers);

	}

	public static function selectColumnFamily($column_family_name)
	{

require_once ('phpcassa/columnfamily.php');

		return new ColumnFamily(self::$pool, $column_family_name);

	}

} // End of Cassandra
