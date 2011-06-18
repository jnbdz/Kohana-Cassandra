<?php

class Kohana_Loadphpcassa {

	public function __construct()
	{

		require ('phpcassa/connection.php');
                require ('phpcassa/columnfamily.php');

	}

}
