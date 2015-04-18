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

$site = ($addmefast->addSite('twitter/tweets', 'Amazing, AddMeFastAPI is working! http://goo.gl/av6ieV', 2, array('ukraine', 'germany', 'united states'), null, 1000, 200));

print_r($site);

print_r($addmefast->touchSite($site['id'], 'start'));

print_r($addmefast->getSites());

die;

