#!/usr/bin/env php
<?php 

use App\Environment;
use App\Log;
use App\Monitor;
use Tokenly\ConsulHealthDaemon\ConsulClient;

require __DIR__.'/../vendor/autoload.php';
Environment::init(dirname(__DIR__));

$consul_client = new ConsulClient(getenv('CONSUL_URL'));
$monitor = Monitor::instance();

Log::debug('Begin monitor');

$start = time();
while (true) {
    try {
        $monitor->sendStatusUpdatesToConsul($consul_client);

    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()."\n";
        Log::warn("ERROR: ".$e->getMessage());
        sleep(5);
    }

    sleep(15);
    if (time() - $start > 300) {
        // 5 minutes
        Log::debug("monitor process still alive");
        $start = time();
    }

}

