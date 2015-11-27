<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup
// for more information
//umask(0000);

$loader = require __DIR__.'/../app/autoload.php';
require_once __DIR__.'/../app/MicroKernel.php';

try {
    $app = new MicroKernel('dev', true);
    $app->loadClassCache();

    $app->handle(Request::createFromGlobals())->send();
} catch (\Exception $e) {
    print_r($e->getMessage());
    print_r($e->getTraceAsString());
}
