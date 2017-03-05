<?php

require_once "vendor/autoload.php";
$appConfig = require('config.php');

date_default_timezone_set($appConfig['timezone']);

$isDevMode = true;
$config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration([__DIR__."/src/models"], $isDevMode);

$conn = \Doctrine\DBAL\DriverManager::getConnection([
	'url' => $appConfig['db'],
], (new \Doctrine\DBAL\Configuration()));

$entityManager = \Doctrine\ORM\EntityManager::create($conn, $config);