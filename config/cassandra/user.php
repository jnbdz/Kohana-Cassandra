<?php

return array
(
	'rules' => array
	(
		'username' => array(
			array('not_empty'),
			array('min_length', array(':value', 4)),
			array('max_length', array(':value', 32)),
			array('regex', array(':value', '/^[-\pL\pN_.]++$/uD')),
			array(array($this, 'username_available'), array(':validation', ':field')),
		),
		'password' => array(
			array('not_empty'),
			array('min_length', array(':value', 8)),
		),
		'password_confirm' => array(
			array('matches', array(':validation', ':field', 'password')),
		),
		'email' => array(
			array('not_empty'),
			array('min_length', array(':value', 4)),
			array('max_length', array(':value', 127)),
			array('email'),
			array(array($this, 'email_available'), array(':validation', ':field')),
		),
	),
	'password' => array(
		array(array(Auth::instance(), 'hash'))
	),
);
