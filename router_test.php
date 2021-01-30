<?php
// router.php
require __DIR__ . '/vendor/autoload.php';

if (preg_match('/\.(?:png|jpg|jpeg|gif)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // serve the requested resource as-is.
} else {
    //echo "<p>Welcome to PHP</p>";
     $test = \Server\Router::test();
     echo("Hello world, test: {$test}\n");
//    foreach (scandir('.') as $i) {
//        echo("<li>{$i}</li>");
//    }
}

