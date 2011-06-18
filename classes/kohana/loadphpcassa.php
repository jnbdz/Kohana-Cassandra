<?php

class Kohana_Loadphpcassa {

	public function __construct()
	{

		require_once ('phpcassa/connection.php');
                require_once ('phpcassa/columnfamily.php');

	}

}
