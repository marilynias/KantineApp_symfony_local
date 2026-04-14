<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// ensure a fresh cache when debug mode is disabled
(new \Symfony\Component\Filesystem\Filesystem())->remove(__DIR__.'/../var/cache/test');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
