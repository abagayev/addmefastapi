<?php

require_once 'lib/config.class.php';
require_once 'addmefast.class.php';

$config = new Config;

$addmefast = new AddMeFast(
        $config::get('addmefast', 'email'),
        $config::get('addmefast', 'password'),
        $config::get('addmefast', 'cookie')
);

$addmefast->getSites();

die;

