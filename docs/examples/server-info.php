<?php
//
// This example demonstrates how to get the apm server information
//
// @link https://www.elastic.co/guide/en/apm/server/7.3/server-info.html
//
require __DIR__ . '/vendor/autoload.php';

use Nipwaayoni\Agent;

$config = [
    'serviceName'    => 'examples',
    'serviceVersion' => '1.0.0-beta',
];

$agent = (new \Nipwaayoni\AgentBuilder())
    ->withConfig(new Nipwaayoni\Config($config))
    ->build();

$info = $agent->info();

var_dump($info->getStatusCode());
var_dump($info->getBody()->getContents());
