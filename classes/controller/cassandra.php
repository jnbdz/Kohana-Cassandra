<?php
/**
* Cassandra
*
* @package        Cassandra
* @author         Jean-Nicolas Boulay Desjardins
* @copyright      (c) 2011 Jean-Nicolas Boulay Desjardins
* @license        MIT
*/

class Cassandra {

	protected $config = array();
	protected $keyspace = NULL;
	protected $servers = array();
	
	public function __construct($config = FALSE, $column_family)
	{
		require_once Kohana::find_file('vendor', 'phpcassa/connection.php');
		require_once Kohana::find_file('vendor', 'phpcassa/columnfamily.php');

		if (is_string($config))
		{

			$name = $config;

			// Test the config group name
			if (($config = Kohana::config('cassandra.'.$config)) === NULL)
				throw new Kohana_Exception('The :name: group is not defined in your cassandra configuration.', array(':name:' => $name));
		}
		elseif (is_array($config))
		{

			// Append the default configuration options
			$config += Kohana::config('cassandra.default');

		}
		else
		{

			// Load the default group
			$config = Kohana::config('cassandra.default');

		}

		$this->config = $config;

		$this->servers = $this->config['servers'];
		$this->keyspace = $this->config['keyspace'];

		$this->pool = new ConnectionPool($this->keyspace, $this->servers);

	}

	public function selectColumnFamily($column_family_name)
	{

		$this->column_family = new ColumnFamily($this->pool, $column_family_name);

	}

	public function insert($row_key, $data, $timestamp = null, $ttl = null, $write_consistency_level = null)
	{

		$this->column_family->insert($row_key, $data, $timestamp, $ttl, $write_consistency_level);

	}

	public function batch_insert($row_key, $data, $timestamp = null, $ttl = null, $write_consistency_level = null)
	{

		$this->column_family->batch_insert($data, $timestamp, $ttl, $write_consistency_level);

	}

	public function batch_insert($rows, $timestamp = null, $ttl = null, $write_consistency_level = null)
	{

		$this->column_family->batch_insert($rows, $timestamp, $ttl, $write_consistency_level);

	}

}