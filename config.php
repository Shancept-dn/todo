<?php

return [
	//Default timezone
	'timezone' => 'Asia/Novosibirsk',

	//DB settings
	'db' => [
		//connection string "mysql://{USER}:{PASSWORD}@{HOST}/{DATABASE}"
		'url' => 'mysql://root@localhost/todo',
		'isDevMode' => true,
		'models' => __DIR__.'/src/models'
	],

	//Настройки memcached
	'memcached' => [
		'host' => 'localhost',
		'port' => 11211,
	]
];