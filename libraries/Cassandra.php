<?php
/**
* Cassandra
*
* @package        Cassandra
* @author         Jean-Nicolas Boulay Desjardins
* @copyright      (c) 2011 Jean-Nicolas Boulay Desjardins
* @license        http://www.opensource.org/licenses/isc-license.txt
*/

class Cassandra {

	protected $config = array();
	protected $keyspace = NULL;
	protected $servers = array();
	
	public function __construct()
	{
		require_once Kohana::find_file('vendor', 'phpcassa/connection.php');
		require_once Kohana::find_file('vendor', 'phpcassa/columnfamily.php');

		// Test the config group name
		$config = Kohana::config('cassandra');

		$this->config = $config;

		$this->servers = $this->config['servers'];
		$this->keyspace = $this->config['keyspace'];

		$this->pool = new ConnectionPool($this->keyspace, $this->servers);

	}

	public function selectColumnFamily($column_family_name)
	{

		return new ColumnFamily($this->pool, $column_family_name);

	}

} // End of Cassandra
