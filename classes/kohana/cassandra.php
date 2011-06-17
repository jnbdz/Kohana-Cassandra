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

		self::$pool = new connectcassandra();

	}

	public static function selectColumnFamily($column_family_name)
	{

		return new ColumnFamily(self::$pool, $column_family_name);

	}

} // End of Cassandra
