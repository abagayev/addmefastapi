<?php

require_once 'vendor/autoload.php';
require_once 'addmefast.class.php';

// create an object of class

$addmefast = new AddMeFast(
        'YOUR ADDMEFAST LOGIN EMAIL',
        'YOUR ADDMEFAST LOGIN PASSWORD'
);

// create a message

$date = (new \DateTime)->format('Y-m-d');
$message = "Amazing, today is $date and AddMeFastAPI is working! http://goo.gl/av6ieV";

// add site and show results

$site = $addmefast->addSite(
    'twitter/tweets',                        // site type
    $message,                                // your message
    2,                                       // points per click
    ['ukraine', 'germany', 'united states'], // countries list
    null,                                    // title for your site
    1000,                                    // total clicks
    200                                      // daily clicks
);

print_r($site);

// start new site and show results

$result = $addmefast->touchSite($site['id'], 'start');
print_r($result);

// get list of your sites

$sites = $addmefast->getSites();
print_r($sites);

die;

