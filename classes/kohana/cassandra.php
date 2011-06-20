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

	public function init()
	{

                require_once ('phpcassa/connection.php');
                require_once ('phpcassa/columnfamily.php');

                $config = Kohana::config('cassandra');

                $servers = $config['servers'];
                $keyspace = $config['keyspace'];
	
		self::$pool = new ConnectionPool($keyspace, $servers);

	}

	public static function selectColumnFamily($col_fam)
	{

		return new ColumnFamily(self::$pool, $col_fam);

	}

} // End of Cassandra
