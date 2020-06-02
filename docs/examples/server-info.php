<?php
//
// This example demonstrates how to get the apm server information
//
// @link https://www.elastic.co/guide/en/apm/server/7.3/server-info.html
//
require __DIR__ . '/vendor/autoload.php';

use Nipwaayoni\Agent;

$config = [
    'appName'    => 'examples',
    'appVersion' => '1.0.0-beta',
];

$agent = (new \Nipwaayoni\AgentBuilder())
    ->withConfig(new \Nipwaayoni\Helper\Config($config))
    ->make();

$info = $agent->info();

var_dump($info->getStatusCode());
var_dump($info->getBody()->getContents());
