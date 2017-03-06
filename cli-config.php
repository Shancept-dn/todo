<?php

$config = require('bootstrap.php');

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet(Api::app($config)->db);
