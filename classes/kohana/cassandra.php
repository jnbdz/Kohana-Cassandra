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

	public static function Util()
	{

		self::init();

		return new CassandraUtil();

	}

	public static function selectColumnFamily($col_fam, $autopack_names = TRUE, $autopack_values = TRUE, $read_consistency_level = 1, $write_consistency_level = 1, $buffer_size = 1024)
	{

		self::init();

		return self::$ColumnFamily = new ColumnFamily(self::$pool, $col_fam, $autopack_names, $autopack_values, $read_consistency_level, $write_consistency_level, $buffer_size);

	}

	public static function getIndexedSlices($indexes)
	{

		$index = array();

		foreach($indexes as $col => $val)
		{

			array_push($index, CassandraUtil::create_index_expression($col, $val));

		}

		$index_clause = CassandraUtil::create_index_clause($index);

		return self::$ColumnFamily->get_indexed_slices($index_clause);

	}

	public static function getCounter($row, $col, $col_fam = 'Counters')
	{
		return self::selectColumnFamily($col_fam)->get($row, array('Users'));
	}

	public static function incrCounter($row, $col, $incr_by, $col_fam = NULL)
	{
		$counter = self::getCounter($row, $col);
		return self::$ColumnFamily->set($row, array($col => $counter + $incr_by));
	}

	public static function decrCounter($row, $col, $decr_by, $col_fam = NULL)
	{
		$counter = self::getCounter($row, $col);
		return self::$ColumnFamily->set($row, array($col => $counter - $decr_by));
	}

} // End of Cassandra
