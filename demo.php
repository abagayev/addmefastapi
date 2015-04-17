<pre><small>
<?php

require_once 'lib/config.class.php';
require_once 'addmefast.class.php';

$config = new Config;

$addmefast = new AddMeFast(
        $config::get('addmefast', 'email'),
        $config::get('addmefast', 'password'),
        $config::get('other', 'cookie')
);

$addmefast->addSite('twitter/followers', 'nedavayruby', 5, 1);

$addmefast->getSites();

die;

