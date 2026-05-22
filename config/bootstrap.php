<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->usePutenv(true)->bootEnv(dirname(__DIR__).'/.env');
} else {
    (new Dotenv())->usePutenv(true)->loadEnv(dirname(__DIR__).'/.env');
}

