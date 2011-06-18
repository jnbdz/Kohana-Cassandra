<?php

class Kohana_Connectcassandra {

	public function __construct()
	{

		require_once ('phpcassa/connection.php');
		require_once ('phpcassa/columnfamily.php');

		$config = Kohana::config('cassandra');

                $servers = $config['servers'];
                $keyspace = $config['keyspace'];

                return new ConnectionPool($keyspace, $servers);

	}

}
