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
	public static $ColumnFamily = NULL;

	public static function init()
	{

		if (self::$pool != null) {
			return;
		}

                require_once ('phpcassa/connection.php');
                require_once ('phpcassa/columnfamily.php');

                $config = Kohana::config('cassandra');

                $servers = $config['servers'];
                $keyspace = $config['keyspace'];
	
		self::$pool = new ConnectionPool($keyspace, $servers);

	}

	public static function selectColumnFamily($col_fam)
	{

		self::init();

		return self::$ColumnFamily = new ColumnFamily(self::$pool, $col_fam);

	}

	public static function getIndexedSlices($indexes)
	{

		$index = array();

		foreach($indexes as $col => $val)
		{

			$index = array_push($index, CassandraUtil::create_index_expression($col, $val));

		}
var_dump($index);
		$index_clause = CassandraUtil::create_index_clause($index);

		return self::$ColumnFamily->get_indexed_slices($index_clause);

	}

} // End of Cassandra
