<?php

require_once "vendor/autoload.php";

$config = require('config.php');

date_default_timezone_set($config['timezone']);

return $config;
