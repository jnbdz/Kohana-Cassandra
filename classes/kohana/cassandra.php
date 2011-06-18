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

	public static $pool = NULL;

	public function __construct()
	{

		require ('phpcassa/connection.php');
                require ('phpcassa/columnfamily.php');

                $config = Kohana::config('cassandra');

                $servers = $config['servers'];
                $keyspace = $config['keyspace'];

                self::$pool = new ConnectionPool($keyspace, $servers);

	}

	public static function connection()
	{

		return self::$pool;

	} 

} // End of Cassandra
