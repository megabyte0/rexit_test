<?php

require __DIR__ . '/vendor/autoload.php';

$dbModel = NULL;
$seeder = new \Seeder\Seeder($dbModel);
$seeder->connect();
$seeder->seed();
